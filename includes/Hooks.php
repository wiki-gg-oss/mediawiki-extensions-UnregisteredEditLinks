<?php
namespace MediaWiki\Extension\UnregisteredEditLinks;

use Config;
use MediaWiki\MainConfigNames;
use SkinTemplate;
use SpecialPage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\RestrictionStore;
use Title;

class Hooks implements
    \MediaWiki\Hook\SkinTemplateNavigation__UniversalHook,
    \MediaWiki\Hook\LoginFormValidErrorMessagesHook
{
    public const MSG_CREATE_ACCOUNT_TO_EDIT = 'accountrequiredtoedit';

    private Config $config;
    private RestrictionStore $restrictionStore;
    private bool $showCreateInContentNs;
    private bool $showCreateIfCanExist;

    public function __construct(
        Config $config,
        RestrictionStore $restrictionStore
    ) {
        $this->config = $config;
        $this->restrictionStore = $restrictionStore;
        $this->showCreateInContentNs = $config->get( 'AEFAdvertiseCreationInContentNs' );
        $this->showCreateIfCanExist = $config->get( 'AEFAdvertiseCreationIfCanExist' );
    }

    public function onLoginFormValidErrorMessages( array &$messages ) {
        $messages[] = self::MSG_CREATE_ACCOUNT_TO_EDIT;
    }

    public function onSkinTemplateNavigation__Universal( $skin, &$links ): void {
        $nsProtections = $this->config->get( MainConfigNames::NamespaceProtection );
    
        // Check if 'views' navigation is defined, and 'viewsource' is defined within; otherwise do not run
        if ( isset( $links['views'] ) ) {
            $title = $skin->getRelevantTitle();

            $shouldModify = $this->checkCriteria( $title, $links );

            if ( !$shouldModify ) {
                return;
            }

            // Require that the user is an anon
            if ( $skin->getAuthority()->getUser()->isRegistered() ) {
                $nsIndex = $title->getNamespace();
                // Check namespace restrictions
                if ( isset( $nsProtections[ $nsIndex ] )
                    && !self::doUsersProbablyHaveTheseRights( $nsProtections[ $nsIndex ] ) ) {
                    return;
                }
                // Check page restrictions
                if ( !self::doUsersProbablyHaveTheseRights( $this->restrictionStore->getRestrictions( $title, 'edit' ) ) ) {
                    return;
                }

                // Prepare the action link
                $injection = self::getActionLink( $skin, $title );
                // Inject the new link onto second position
                $links['views'] = array_slice( $links['views'], 0, 1, true ) + $injection +
                    array_slice( $links['views'], 1, null, true );
            }
        }
    }

    private function checkCriteria( Title $title, array &$links ): bool {
        if ( isset( $links['views']['viewsource'] ) && !isset( $links['views']['edit'] ) ) {
            return true;
        }

        if ( !$title->exists() && $title->canExist() && $title->isContentPage() ) {
            return true;
        }

        return false;
    }

    private static function getActionLink( SkinTemplate $skin, Title $title ): array {
        return [
            'edit' => [
                'class' => false,
                'text' => wfMessage( $title->exists() ? 'unregistered-edit' : 'unregistered-create' )->setContext( $skin->getContext() )->text(),
                'href' => SpecialPage::getTitleFor( 'CreateAccount' )->getLocalURL( [
                    'warning' => self::MSG_CREATE_ACCOUNT_TO_EDIT,
                    'returnto' => $title->getPrefixedDBKey(),
                    'returntoquery' => 'action=edit'
                ] ),
                'primary' => true
            ]
        ];
    }

    private static function doUsersProbablyHaveTheseRights( /*string|array*/ $rights ) {
        if ( is_array( $rights ) )
            return empty( $rights ) || ( count( $rights ) === 1 && ( $rights[0] === 'autoconfirmed' || $rights[0] === '' ) );
        
        return $rights === '' || $rights === 'autoconfirmed';
    }
}
