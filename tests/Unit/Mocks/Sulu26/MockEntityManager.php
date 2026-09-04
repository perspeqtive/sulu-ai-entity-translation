<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Mocks\Sulu26;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
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

    public function getRepository($className): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getCache(): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getConnection(): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getExpressionBuilder(): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function beginTransaction(): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function transactional($func): void
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

    public function createQuery($dql = ''): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function createNamedQuery($name): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function createNativeQuery($sql, ResultSetMapping $rsm): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function createNamedNativeQuery($name): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function createQueryBuilder(): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getReference($entityName, $id): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getPartialReference($entityName, $identifier): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function copy($entity, $deep = false): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function lock($entity, $lockMode, $lockVersion = null): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getEventManager(): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getConfiguration(): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function isOpen()
    {
        return $this->open;
    }

    public function getUnitOfWork(): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getHydrator($hydrationMode): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function newHydrator($hydrationMode): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getProxyFactory(): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getFilters(): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function isFiltersStateClean(): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function hasFilters(): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function getClassMetadata($className): void
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
    public function find(string $className, $id)
    {
        $entity = $this->entities[$className][(string) (is_scalar($id) ? $id : '')] ?? null;

        /** @var T|null $entity */
        return $entity;
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

    public function refresh(object $object): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function flush(): void
    {
        ++$this->flushes;
    }

    public function getMetadataFactory(): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function initializeObject(object $obj): void
    {
        $this->unsupported(__FUNCTION__);
    }

    public function contains(object $object): void
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
