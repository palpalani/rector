<?php

namespace Rector\Tests\Rector\Property\RemoveNullPropertyInitializationRector\Fixture;

class SomeClass
{
    private $lowerCase = null;

    private $titleCaseNullConstant = Null;

    private $upperCaseNullConstant = NULL;

    private $notANull = 'null';

    /**
     * @var null
     */
    private $nullInPhpDocs;

    private $notANullWithDoubleQuotes = "null";

}

?>
-----
<?php

namespace Rector\Tests\Rector\Property\RemoveNullPropertyInitializationRector\Fixture;

class SomeClass
{
    private $lowerCase;

    private $titleCaseNullConstant;

    private $upperCaseNullConstant;

    private $notANull = 'null';

    /**
     * @var null
     */
    private $nullInPhpDocs;

    private $notANullWithDoubleQuotes = "null";

}

?>
