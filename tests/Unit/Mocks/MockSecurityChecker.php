<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Mocks;

use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

final class MockSecurityChecker implements SecurityCheckerInterface
{
    public function __construct(
        private readonly bool $granted = true,
    ) {
    }

    /**
     * @param mixed $subject
     * @param mixed $permission
     */
    public function checkPermission($subject, $permission)
    {
        return $this->granted;
    }

    /**
     * @param mixed $subject
     * @param mixed $permission
     */
    public function hasPermission($subject, $permission)
    {
        return $this->granted;
    }
}
