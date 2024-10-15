<?php
namespace MediaWiki\Extension\UnregisteredEditLinks;

use MediaWiki\Config\Config;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\Authority;
use MediaWiki\Permissions\GroupPermissionsLookup;
use MediaWiki\Permissions\RestrictionStore;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use SkinTemplate;

final class UnregisteredEditLinks {
    public const SERVICE_NAME = 'UnregisteredEditLinks';

    /**
     * @internal Use only in ServiceWiring
     */
    public const CONSTRUCTOR_OPTIONS = [
        MainConfigNames::NamespaceProtection,
        'UnregisteredEditLinksGroups',
    ];

    public const MSG_CREATE_ACCOUNT_TO_EDIT = 'accountrequiredtoedit';

    public function __construct(
        private readonly ServiceOptions $options,
        private readonly RestrictionStore $restrictionStore,
        private readonly GroupPermissionsLookup $groupPermissionsLookup
    ) {
        $options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
    }

    public function checkTitle( Title $title ): bool {
        if ( !( $title->canExist() && $title->isContentPage() ) ) {
            return false;
        }

        $rights = [
            // Namespace protection
            $this->options->get( MainConfigNames::NamespaceProtection )[ $title->getNamespace() ] ?? 'edit',
            // Page protection
            ...$this->restrictionStore->getRestrictions( $title, 'edit' ),
        ];

        foreach ( $this->options->get( 'UnregisteredEditLinksGroups' ) as $group ) {
            foreach ( $rights as $right ) {
                // Normalise the right (autoconfirmed is deprecated)
                if ( $right === 'autoconfirmed' ) {
                    $right = 'editsemiprotected';
                }

                if ( !$this->groupPermissionsLookup->groupHasPermission( $group, $right ) ) {
                    return false;
                }
            }
        }

        return true;
    }

    public function canUserEditAnything( Authority $authority ): bool {
        return $this->groupPermissionsLookup->groupHasPermission( '*', 'edit' ) || $authority->isRegistered();
    }

    public function getGatedEditLink( Title $title ) {
        return SpecialPage::getTitleFor( 'CreateAccount' )->getLocalURL( [
            'warning' => self::MSG_CREATE_ACCOUNT_TO_EDIT,
            'returnto' => $title->getPrefixedDBKey(),
            'returntoquery' => 'action=edit'
        ] );
    }
}
