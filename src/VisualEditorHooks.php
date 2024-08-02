<?php
namespace MediaWiki\Extension\UnregisteredEditLinks;

use MediaWiki\Output\OutputPage;
use Skin;

// Can't explicitly implement VisualEditorBeforeEditorHook as some wikis won't have it loaded
final class VisualEditorHooks {
    public function __construct(
        private readonly UnregisteredEditLinks $uel
    ) { }

	/**
	 * Do not load VisualEditor for anonymous users if they have no edit permissions.
	 *
	 * @param OutputPage $output
	 * @param Skin $skin
	 * @return bool
	 */
	public function onVisualEditorBeforeEditor( OutputPage $output, Skin $skin ): bool {
		return $this->uel->canUserEditAnything( $output->getAuthority() );
    }
}
