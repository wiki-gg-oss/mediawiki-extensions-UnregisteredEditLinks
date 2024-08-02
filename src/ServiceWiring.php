<?php

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\UnregisteredEditLinks\UnregisteredEditLinks;
use MediaWiki\MediaWikiServices;

return [
    UnregisteredEditLinks::SERVICE_NAME => static function (
        MediaWikiServices $services
    ): UnregisteredEditLinks {
        return new UnregisteredEditLinks(
            new ServiceOptions(
                UnregisteredEditLinks::CONSTRUCTOR_OPTIONS,
                $services->getMainConfig()
            ),
            $services->getRestrictionStore(),
            $services->getGroupPermissionsLookup()
        );
    },
];
