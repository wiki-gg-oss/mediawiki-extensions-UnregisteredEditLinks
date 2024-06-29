<?php
namespace MediaWiki\Extension\UnregisteredEditLinks;

use MediaWiki\Config\Config;
use MediaWiki\MainConfigNames;
use MediaWiki\Permissions\RestrictionStore;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use SkinTemplate;

class Hooks implements
    \MediaWiki\Hook\SkinTemplateNavigation__UniversalHook,
    \MediaWiki\Hook\LoginFormValidErrorMessagesHook
{
    private Config $config;
    private RestrictionStore $restrictionStore;

    public function __construct(
        Config $config,
        RestrictionStore $restrictionStore
    ) {
        $this->config = $config;
        $this->restrictionStore = $restrictionStore;
    }

    public function onLoginFormValidErrorMessages( array &$messages ) {
        $messages[] = UnregisteredEditLinks::MSG_CREATE_ACCOUNT_TO_EDIT;
    }

    public function onSkinTemplateNavigation__Universal( $skin, &$links ): void {
        $nsProtections = $this->config->get( MainConfigNames::NamespaceProtection );
    
        // Check if 'views' navigation is defined, and 'viewsource' is defined within; otherwise do not run
        if ( isset( $links['views'] ) ) {
            $title = $skin->getRelevantTitle();

            $shouldModify = ( isset( $links['views']['viewsource'] ) && !isset( $links['views']['edit'] ) )
                || UnregisteredEditLinks::checkTitleCriteria( $title, $links );
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

    private static function getActionLink( SkinTemplate $skin, Title $title ): array {
        return [
            'edit' => [
                'class' => false,
                'text' => wfMessage( $title->exists() ? 'unregistered-edit' : 'unregistered-create' )
                    ->setContext( $skin->getContext() )
                    ->text(),
                'href' => UnregisteredEditLinks::getGatedEditLink( $title ),
                'primary' => true
            ]
        ];
    }

    private static function doUsersProbablyHaveTheseRights( /*string|array*/ $rights ) {
        return UnregisteredEditLinks::doUsersProbablyHaveTheseRights( $rights );
    }
}
