<?php

declare(strict_types=1);

namespace Rector\PHPStanStaticTypeMapper\TypeMapper;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use Rector\PHPStan\Type\AliasedObjectType;
use Rector\PHPStan\Type\FullyQualifiedObjectType;
use Rector\PHPStan\Type\ShortenedObjectType;
use Rector\PHPStanStaticTypeMapper\Contract\TypeMapperInterface;

final class ObjectTypeMapper implements TypeMapperInterface
{
    public function getNodeClass(): string
    {
        return ObjectType::class;
    }

    /**
     * @param ObjectType $type
     */
    public function mapToPHPStanPhpDocTypeNode(Type $type): TypeNode
    {
        return new IdentifierTypeNode('\\' . $type->getClassName());
    }

    /**
     * @param ObjectType $type
     */
    public function mapToPhpParserNode(Type $type, ?string $kind = null): ?Node
    {
        if ($type instanceof ShortenedObjectType) {
            return new FullyQualified($type->getFullyQualifiedName());
        }

        if ($type instanceof AliasedObjectType) {
            return new Name($type->getClassName());
        }

        if ($type instanceof FullyQualifiedObjectType) {
            return new FullyQualified($type->getClassName());
        }

        if ($type instanceof GenericObjectType) {
            if ($type->getClassName() === 'object') {
                return new Identifier('object');
            }
        }

        // fallback
        return new FullyQualified($type->getClassName());
    }

    public function mapToDocString(Type $type, ?Type $parentType = null): string
    {
        return $type->describe(VerbosityLevel::typeOnly());
    }
}
