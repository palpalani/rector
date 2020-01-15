<?php

declare(strict_types=1);

namespace Rector\PHPStanStaticTypeMapper\TypeMapper;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType as PhpParserUnionType;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;
use Rector\AttributeAwarePhpDoc\Ast\Type\AttributeAwareUnionTypeNode;
use Rector\Exception\ShouldNotHappenException;
use Rector\Php\PhpVersionProvider;
use Rector\PHPStanStaticTypeMapper\Contract\TypeMapperInterface;
use Rector\PHPStanStaticTypeMapper\PHPStanStaticTypeMapper;
use Rector\PHPStanStaticTypeMapper\TypeAnalyzer\UnionTypeAnalyzer;
use Rector\ValueObject\PhpVersionFeature;

final class UnionTypeMapper implements TypeMapperInterface
{
    /**
     * @var PHPStanStaticTypeMapper
     */
    private $phpStanStaticTypeMapper;

    /**
     * @var PhpVersionProvider
     */
    private $phpVersionProvider;

    /**
     * @var UnionTypeAnalyzer
     */
    private $unionTypeAnalyzer;

    public function __construct(PhpVersionProvider $phpVersionProvider, UnionTypeAnalyzer $unionTypeAnalyzer)
    {
        $this->phpVersionProvider = $phpVersionProvider;
        $this->unionTypeAnalyzer = $unionTypeAnalyzer;
    }

    public function getNodeClass(): string
    {
        return UnionType::class;
    }

    /**
     * @param UnionType $type
     */
    public function mapToPHPStanPhpDocTypeNode(Type $type): TypeNode
    {
        $unionTypesNodes = [];

        foreach ($type->getTypes() as $unionedType) {
            $unionTypesNodes[] = $this->phpStanStaticTypeMapper->mapToPHPStanPhpDocTypeNode($unionedType);
        }

        $unionTypesNodes = array_unique($unionTypesNodes);

        return new AttributeAwareUnionTypeNode($unionTypesNodes);
    }

    /**
     * @required
     */
    public function autowire(PHPStanStaticTypeMapper $phpStanStaticTypeMapper): void
    {
        $this->phpStanStaticTypeMapper = $phpStanStaticTypeMapper;
    }

    /**
     * @param UnionType $type
     */
    public function mapToPhpParserNode(Type $type, ?string $kind = null): ?Node
    {
        // match array types
        $arrayNode = $this->matchArrayTypes($type);
        if ($arrayNode !== null) {
            return $arrayNode;
        }

        // special case for nullable
        $nullabledType = $this->matchTypeForNullableUnionType($type);
        if ($nullabledType === null) {
            // use first unioned type in case of unioned object types
            return $this->matchTypeForUnionedObjectTypes($type);
        }

        $nullabledTypeNode = $this->phpStanStaticTypeMapper->mapToPhpParserNode($nullabledType);
        if ($nullabledTypeNode === null) {
            return null;
        }

        if ($nullabledTypeNode instanceof NullableType) {
            return $nullabledTypeNode;
        }

        if ($nullabledTypeNode instanceof PhpParserUnionType) {
            throw new ShouldNotHappenException();
        }

        return new NullableType($nullabledTypeNode);
    }

    private function matchArrayTypes(UnionType $unionType): ?Identifier
    {
        $unionTypeAnalysis = $this->unionTypeAnalyzer->analyseForNullableAndIterable($unionType);
        if ($unionTypeAnalysis === null) {
            return null;
        }

        $type = $unionTypeAnalysis->hasIterable() ? 'iterable' : 'array';
        if ($unionTypeAnalysis->isNullableType()) {
            return new Identifier('?' . $type);
        }

        return new Identifier($type);
    }

    private function matchTypeForNullableUnionType(UnionType $unionType): ?Type
    {
        if (count($unionType->getTypes()) !== 2) {
            return null;
        }

        $firstType = $unionType->getTypes()[0];
        $secondType = $unionType->getTypes()[1];

        if ($firstType instanceof NullType) {
            return $secondType;
        }

        if ($secondType instanceof NullType) {
            return $firstType;
        }

        return null;
    }

    /**
     * @return Name|FullyQualified|PhpParserUnionType|null
     */
    private function matchTypeForUnionedObjectTypes(UnionType $unionType): ?Node
    {
        $phpParserUnionType = $this->matchPhpParserUnionType($unionType);
        if ($phpParserUnionType !== null) {
            return $phpParserUnionType;
        }

        // the type should be compatible with all other types, e.g. A extends B, B
        $compatibleObjectCandidate = $this->resolveCompatibleObjectCandidate($unionType);
        if ($compatibleObjectCandidate === null) {
            return null;
        }

        return new FullyQualified($compatibleObjectCandidate);
    }

    private function matchPhpParserUnionType(UnionType $unionType): ?PhpParserUnionType
    {
        if (! $this->phpVersionProvider->isAtLeast(PhpVersionFeature::UNION_TYPES)) {
            return null;
        }

        $phpParserUnionedTypes = [];

        foreach ($unionType->getTypes() as $unionedType) {
            /** @var Identifier|Name|null $phpParserNode */
            $phpParserNode = $this->phpStanStaticTypeMapper->mapToPhpParserNode($unionedType);
            if ($phpParserNode === null) {
                return null;
            }

            $phpParserUnionedTypes[] = $phpParserNode;
        }

        return new PhpParserUnionType($phpParserUnionedTypes);
    }

    private function areTypeWithClassNamesRelated(TypeWithClassName $firstType, TypeWithClassName $secondType): bool
    {
        if (is_a($firstType->getClassName(), $secondType->getClassName(), true)) {
            return true;
        }

        return is_a($secondType->getClassName(), $firstType->getClassName(), true);
    }

    private function resolveCompatibleObjectCandidate(UnionType $unionType): ?string
    {
        foreach ($unionType->getTypes() as $unionedType) {
            if (! $unionedType instanceof TypeWithClassName) {
                return null;
            }

            foreach ($unionType->getTypes() as $nestedUnionedType) {
                if (! $nestedUnionedType instanceof TypeWithClassName) {
                    return null;
                }

                if (! $this->areTypeWithClassNamesRelated($unionedType, $nestedUnionedType)) {
                    continue 2;
                }
            }

            return $unionedType->getClassName();
        }

        return null;
    }
}
