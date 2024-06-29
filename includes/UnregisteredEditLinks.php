<?php
namespace MediaWiki\Extension\UnregisteredEditLinks;

use MediaWiki\Config\Config;
use MediaWiki\MainConfigNames;
use MediaWiki\Permissions\RestrictionStore;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use SkinTemplate;

final class UnregisteredEditLinks {
    public const MSG_CREATE_ACCOUNT_TO_EDIT = 'accountrequiredtoedit';

    public static function checkTitleCriteria( Title $title ): bool {
        return !$title->exists() && $title->canExist() && $title->isContentPage();
    }

    public static function getGatedEditLink( Title $title ) {
        return SpecialPage::getTitleFor( 'CreateAccount' )->getLocalURL( [
            'warning' => self::MSG_CREATE_ACCOUNT_TO_EDIT,
            'returnto' => $title->getPrefixedDBKey(),
            'returntoquery' => 'action=edit'
        ] );
    }

    public static function doUsersProbablyHaveTheseRights( /*string|array*/ $rights ) {
        if ( is_array( $rights ) )
            return empty( $rights ) || ( count( $rights ) === 1 && ( $rights[0] === 'autoconfirmed' || $rights[0] === '' ) );
        
        return $rights === '' || $rights === 'autoconfirmed';
    }
}
