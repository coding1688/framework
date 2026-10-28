<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Attributes\MenuAction;
use App\Attributes\MenuPage;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\Admin\AdminMenuRequest;
use App\Http\Resources\Backend\Admin\AdminMenuResource;
use App\Services\Backend\Admin\AdminMenuService;
use Illuminate\Http\JsonResponse;

#[MenuPage('admin.menu', ['en' => 'Backend menus', 'zh_CN' => '后台菜单', 'zh_TW' => '後台選單', 'th' => 'เมนูแผงควบคุม', 'ja' => '管理メニュー', 'ko' => '관리 메뉴'], '/admin/menu', 'admin/menu/List', parent: 'development', icon: 'node-tree')]
class AdminMenuController extends BaseController
{
    public function __construct(private AdminMenuService $service) {}

    #[MenuAction(['en' => 'List', 'zh_CN' => '列表', 'zh_TW' => '列表', 'th' => 'รายการ', 'ja' => '一覧', 'ko' => '목록'], sort: 0)]
    public function list(): JsonResponse
    {
        return apiSuccess(AdminMenuResource::collection($this->service->list()));
    }

    #[MenuAction(['en' => 'Refresh menus', 'zh_CN' => '刷新菜单', 'zh_TW' => '重新整理選單', 'th' => 'รีเฟรชเมนู', 'ja' => 'メニューを更新', 'ko' => '메뉴 새로고침'], sort: 5)]
    public function sync(AdminMenuRequest $request): JsonResponse
    {
        return apiSuccess($this->service->sync($request->validated()), message: __('backend/menu.discovery_saved'));
    }

    #[MenuAction(['en' => 'Create', 'zh_CN' => '新增', 'zh_TW' => '新增', 'th' => 'สร้าง', 'ja' => '作成', 'ko' => '생성'], sort: 1)]
    public function create(AdminMenuRequest $request): JsonResponse
    {
        return apiSuccess(new AdminMenuResource($this->service->create($request->validated())), status: 201);
    }

    #[MenuAction(['en' => 'Edit', 'zh_CN' => '编辑', 'zh_TW' => '編輯', 'th' => 'แก้ไข', 'ja' => '編集', 'ko' => '편집'], sort: 2)]
    public function update(AdminMenuRequest $request, int $id): JsonResponse
    {
        $this->service->update($request->validated(), $id);

        return apiSuccess(message: __('backend/common.saved'));
    }

    #[MenuAction(['en' => 'Delete', 'zh_CN' => '删除', 'zh_TW' => '刪除', 'th' => 'ลบ', 'ja' => '削除', 'ko' => '삭제'], sort: 3)]
    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);

        return apiSuccess(message: __('backend/common.saved'));
    }
}
