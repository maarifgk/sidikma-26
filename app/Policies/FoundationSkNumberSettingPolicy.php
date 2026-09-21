<?php
namespace App\Policies;
use App\Models\FoundationSkNumberSetting; use App\Models\User;
class FoundationSkNumberSettingPolicy { public function viewAny(User $u):bool{return $u->isAdminInduk()||($u->can('sk-yayasan.view')&&$u->hasRole('admin-sekolah-madrasah'));} public function view(User $u,FoundationSkNumberSetting $r):bool{return $this->viewAny($u);} public function create(User $u):bool{return $u->isAdminInduk()&&$u->can('sk-yayasan.manage');} public function update(User $u,FoundationSkNumberSetting $r):bool{return $this->create($u);} public function delete(User $u,FoundationSkNumberSetting $r):bool{return $this->create($u);} }
