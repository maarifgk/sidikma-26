<?php
namespace App\Policies;
use App\Models\FoundationSkTemplate; use App\Models\User;
class FoundationSkTemplatePolicy { private function canView(User $u): bool{return $u->isAdminInduk()||$u->can('sk-yayasan.view');} private function manage(User $u): bool{return $u->isAdminInduk()||$u->can('sk-yayasan.manage');} public function viewAny(User $u):bool{return $this->canView($u);} public function view(User $u,FoundationSkTemplate $r):bool{return $this->canView($u);} public function create(User $u):bool{return $this->manage($u);} public function update(User $u,FoundationSkTemplate $r):bool{return $this->manage($u);} public function delete(User $u,FoundationSkTemplate $r):bool{return $this->manage($u);} }
