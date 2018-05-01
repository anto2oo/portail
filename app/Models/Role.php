<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasPermissions;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
	use HasPermissions;

	public static function find(int $id, string $only_for = null) {
		$roles = static::where('id', $id);

		if ($only_for !== null) {
			$group = explode('-', $only_for)[0] ?? $only_for;

			$roles->where(function ($query) use ($group, $only_for) {
				$query->where('only_for', $group)->orWhere('only_for', $only_for);
			});
		}

		return $roles->first();
	}

	public static function findByType(string $type, string $only_for = null) {
		$roles = static::where('type', $type);

		if ($only_for !== null) {
			$group = explode('-', $only_for)[0] ?? $only_for;

			$roles->where(function ($query) use ($group, $only_for) {
				$query->where('only_for', $group)->orWhere('only_for', $only_for);
			});
		}

		return $roles->first();
	}

	public static function getRole($role, string $only_for = null) {
    	if (is_string($role))
    		return static::findByType($role, $only_for);
    	else if (is_int($role))
			return static::find($role, $only_for);
		else
			return $role;
	}

	public static function getRoles($roles, string $only_for = 'users') {
		$group = explode('-', $only_for)[0] ?? $only_for;

		if (is_array($roles)) {
			return static::where(function ($query) use ($roles) {
				$query->whereIn('id', $roles)->orWhereIn('type', $roles);
			})->where(function ($query) use ($group, $only_for) {
				$query->where('only_for', $group)->orWhere('only_for', $only_for);
			})->get();
		}
		else if ($roles instanceof \Illuminate\Database\Eloquent\Model)
			return collect($roles);
		else
			return $roles;
	}

	public static function getRoleAndItsParents($role, string $only_for = null) {
		$role = static::getRole($role, $only_for);

		if ($role === null)
			return null;

	 	$roles = $role->parents;
		$roles->push($role);

		return $roles;
	}

	public function permissions(): BelongsToMany {
		return $this->belongsToMany(Permission::class, 'roles_permissions');
	}

	public function users(): BelongsToMany {
		return $this->belongsToMany(User::class, 'users_roles');
	}

	public function childs(): BelongsToMany {
		return $this->belongsToMany(Role::class, 'roles_parents', 'parent_id', 'role_id');
	}

	public function parents(): BelongsToMany {
		return $this->belongsToMany(Role::class, 'roles_parents', 'role_id', 'parent_id');
	}

	public function allChilds() {
		$childs = collect();

		foreach ($this->childs as $child) {
			$childs->push($child);

			$childs = $childs->merge($child->allChilds());
			$child->makeHidden('childs');
		}

		return $childs->unique('id');
	}

	public function allParents() {
		$parents = collect();

		foreach ($this->parents as $parent) {
			$parents->push($parent);

			$parents = $parents->merge($parent->allChilds());
			$parent->makeHidden('parents');
		}

		return $parents->unique('id');
	}

	public function hasPermissionTo($permission): bool {
		if (is_string($permission))
			$permission = Permission::findByType($permission);
		else if (is_int($permission))
			$permission = Permission::find($permission);

		return $this->permissions->contains('id', $permission->id);
	}

	public function givePermissionTo($permissions) {
		$this->permissions()->withTimestamps()->attach(Permission::getPermissions(stringToArray($permissions), $this->only_for === 'users'));

		return $this;
	}

	public function removePermissionTo($permissions) {
		$this->permissions()->withTimestamps()->detach(Permission::getPermissions(stringToArray($permissions), $this->only_for === 'users'));

		return $this;
	}

	public function assignParentRole($roles) {
		$this->parents()->withTimestamps()->attach(static::getRoles(stringToArray($roles), $this->only_for));

		return $this;
	}

	public function removeParentRole($roles) {
		$this->parents()->withTimestamps()->detach(static::getRoles(stringToArray($roles), $this->only_for));

		return $this;
	}

	public function syncParentRole($roles) {
		$this->parents()->withTimestamps()->sync(static::getRoles(stringToArray($roles), $this->only_for));

		return $this;
	}
}
