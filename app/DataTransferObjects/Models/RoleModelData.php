<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Models;

class RoleModelData
{
    /**
     * The name.
     *
     * @var string|null
     */
    private string|null $name = null;

    /**
     * The guard name.
     *
     * @var string|null
     */
    private string|null $guardName = null;

    /**
     * The description.
     *
     * @var string|null
     */
    private string|null $description = null;

    /**
     * The permissions
     *
     * @var array<int, string>|null
     */
    private array|null $permissions = null;

    /**
     * Get the name.
     *
     * @return string|null
     */
    public function getName(): string|null
    {
        return $this->name;
    }

    /**
     * Set the name.
     *
     * @param string|null $name The name.
     *
     * @return self
     */
    public function setName(string|null $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the description.
     *
     * @return string|null
     */
    public function getDescription(): string|null
    {
        return $this->description;
    }

    /**
     * Set the description.
     *
     * @param string|null $description The description.
     *
     * @return self
     */
    public function setDescription(string|null $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the permissions.
     *
     * @return array<int, string>|null
     */
    public function getPermissions(): array|null
    {
        return $this->permissions;
    }

    /**
     * Set the permissions.
     *
     * @param array<int, string>|null $permissions
     *
     * @return self
     */
    public function setPermissions(array|null $permissions): self
    {
        $this->permissions = $permissions;

        return $this;
    }

    /**
     * Get the guard name.
     *
     * @return string|null
     */
    public function getGuardName(): string|null
    {
        return $this->guardName;
    }

    /**
     * Set the guard name.
     *
     * @param string|null $guardName The guard name.
     *
     * @return self
     */
    public function setGuardName(string|null $guardName): self
    {
        $this->guardName = $guardName;

        return $this;
    }
}
