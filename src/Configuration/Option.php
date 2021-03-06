<?php

declare(strict_types=1);

namespace Rector\Configuration;

final class Option
{
    /**
     * @var string
     */
    public const SOURCE = 'source';

    /**
     * @var string
     */
    public const OPTION_AUTOLOAD_FILE = 'autoload-file';

    /**
     * @var string
     */
    public const OPTION_DRY_RUN = 'dry-run';

    /**
     * @var string
     */
    public const OPTION_OUTPUT_FORMAT = 'output-format';

    /**
     * @var string
     */
    public const OPTION_NO_PROGRESS_BAR = 'no-progress-bar';

    /**
     * @var string
     */
    public const PHP_VERSION_FEATURES = 'php_version_features';

    /**
     * @var string
     */
    public const HIDE_AUTOLOAD_ERRORS = 'hide-autoload-errors';

    /**
     * @var string
     */
    public const OPTION_ONLY = 'only';

    /**
     * @var string
     */
    public const AUTO_IMPORT_NAMES = 'auto_import_names';

    /**
     * @var string
     */
    public const IMPORT_SHORT_CLASSES_PARAMETER = 'import_short_classes';

    /**
     * @var string
     */
    public const EXCLUDE_RECTORS_PARAMETER = 'exclude_rectors';

    /**
     * @var string
     */
    public const MATCH_GIT_DIFF = 'match-git-diff';

    /**
     * @var string
     */
    public const SYMFONY_CONTAINER_XML_PATH_PARAMETER = 'symfony_container_xml_path';

    /**
     * @var string
     */
    public const DUMP = 'dump';
}
