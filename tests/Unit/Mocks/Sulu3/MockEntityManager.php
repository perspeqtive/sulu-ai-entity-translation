<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Mocks\Sulu3;

use DateTimeInterface;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use LogicException;

use function is_scalar;
use function sprintf;

final class MockEntityManager implements EntityManagerInterface
{
    /**
     * @var list<object>
     */
    public array $persisted = [];

    public int $flushes = 0;

    private bool $open = true;

    /**
     * @param array<string, array<array-key, object>> $entities
     */
    public function __construct(
        private readonly array $entities = [],
    ) {
    }

    public function getRepository(string $className): EntityRepository
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getCache(): ?Cache
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getConnection(): Connection
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getMetadataFactory(): ClassMetadataFactory
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getExpressionBuilder(): Expr
    {
        $this->unsupported(__FUNCTION__);
    }

    public function beginTransaction(): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function wrapInTransaction(callable $func): mixed
    {
        $this->unsupported(__FUNCTION__);
    }

    public function commit(): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function rollback(): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function createQuery(string $dql = ''): Query
    {
        $this->unsupported(__FUNCTION__);
    }

    public function createNativeQuery(string $sql, ResultSetMapping $rsm): NativeQuery
    {
        $this->unsupported(__FUNCTION__);
    }

    public function createQueryBuilder(): QueryBuilder
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T|null
     */
    public function find(string $className, mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
    {
        $entity = $this->entities[$className][(string) (is_scalar($id) ? $id : '')] ?? null;

        /** @var T|null $entity */
        return $entity;
    }

    public function refresh(object $object, LockMode|int|null $lockMode = null): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getReference(string $entityName, mixed $id): ?object
    {
        $this->unsupported(__FUNCTION__);
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function lock(object $entity, LockMode|int $lockMode, DateTimeInterface|int|null $lockVersion = null): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getEventManager(): EventManager
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getConfiguration(): Configuration
    {
        $this->unsupported(__FUNCTION__);
    }

    public function isOpen(): bool
    {
        return $this->open;
    }

    public function getUnitOfWork(): UnitOfWork
    {
        $this->unsupported(__FUNCTION__);
    }

    public function newHydrator(string|int $hydrationMode): AbstractHydrator
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getProxyFactory(): ProxyFactory
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getFilters(): FilterCollection
    {
        $this->unsupported(__FUNCTION__);
    }

    public function isFiltersStateClean(): bool
    {
        $this->unsupported(__FUNCTION__);
    }

    public function hasFilters(): bool
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getClassMetadata(string $className): ClassMetadata
    {
        $this->unsupported(__FUNCTION__);
    }

    public function persist(object $object): void
    {
        $this->persisted[] = $object;
    }

    public function remove(object $object): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function clear(): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function detach(object $object): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function flush(): void
    {
        ++$this->flushes;
    }

    public function initializeObject(object $obj): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function isUninitializedObject(mixed $value): bool
    {
        $this->unsupported(__FUNCTION__);
    }

    public function contains(object $object): bool
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * @return never
     */
    private function unsupported(string $method)
    {
        throw new LogicException(sprintf('%s() is not part of what the tests exercise.', $method));
    }
}
