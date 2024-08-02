<?php
namespace MediaWiki\Extension\UnregisteredEditLinks;

use MediaWiki\Title\Title;
use SkinTemplate;

final class Hooks implements
    \MediaWiki\Hook\SkinTemplateNavigation__UniversalHook,
    \MediaWiki\Hook\LoginFormValidErrorMessagesHook
{
    public function __construct(
        private readonly UnregisteredEditLinks $uel
    ) { }

    public function onLoginFormValidErrorMessages( array &$messages ) {
        $messages[] = UnregisteredEditLinks::MSG_CREATE_ACCOUNT_TO_EDIT;
    }

    public function onSkinTemplateNavigation__Universal( $skin, &$links ): void {
        $title = $skin->getRelevantTitle();
        $authority = $skin->getAuthority();

        // Skip if the user is registered
        if ( $authority->getUser()->isRegistered() ) {
            return;
        }

        // Check if 'views' navigation is defined, and 'viewsource' is defined within; otherwise do not run
        if ( !isset( $links['views'] ) ) {
            return;
        }

        if ( !( isset( $links['views']['viewsource'] ) && !isset( $links['views']['edit'] ) ) ) {
            return;
        }

        if ( $this->uel->checkTitle( $title ) ) {
            // Prepare the action link
            $injection = $this->getActionLink( $skin, $title );
            // Inject the new link onto second position
            $links['views'] = array_slice( $links['views'], 0, 1, true ) + $injection +
                array_slice( $links['views'], 1, null, true );
        }
    }

    private function getActionLink( SkinTemplate $skin, Title $title ): array {
        return [
            'edit' => [
                'class' => false,
                'text' => wfMessage( $title->exists() ? 'unregistered-edit' : 'unregistered-create' )
                    ->setContext( $skin->getContext() )
                    ->text(),
                'href' => $this->uel->getGatedEditLink( $title ),
                'primary' => true
            ]
        ];
    }
}
