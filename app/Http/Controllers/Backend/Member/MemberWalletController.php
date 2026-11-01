<?php

namespace App\Http\Controllers\Backend\Member;

use App\Attributes\MenuAction;
use App\Attributes\MenuPage;
use App\Attributes\RequireSecurityPassword;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\Member\MemberWalletRequest;
use App\Http\Resources\Backend\Member\MemberWalletDailyStatResource;
use App\Http\Resources\Backend\Member\MemberWalletResource;
use App\Http\Resources\Backend\Member\MemberWalletTransactionResource;
use App\Services\Backend\Member\MemberWalletService;
use Illuminate\Http\JsonResponse;

#[MenuPage('member.wallet', ['en' => 'User Wallets', 'zh_CN' => '用户钱包', 'zh_TW' => '使用者錢包', 'th' => 'กระเป๋าเงินผู้ใช้', 'ja' => 'ユーザーウォレット', 'ko' => '사용자 지갑'], '/admin/member/wallet', 'member/wallet/List', parent: 'member', icon: 'wallet-3-line', sort: 30)]
class MemberWalletController extends BaseController
{
    public function __construct(private readonly MemberWalletService $service) {}

    #[MenuAction(['en' => 'List', 'zh_CN' => '列表', 'zh_TW' => '列表', 'th' => 'รายการ', 'ja' => '一覧', 'ko' => '목록'])]
    public function list(MemberWalletRequest $request): JsonResponse
    {
        $page = $this->service->list($request->validated());

        return apiPaginate($page, MemberWalletResource::collection($page));
    }

    #[MenuAction(['en' => 'Transaction history', 'zh_CN' => '账变历史', 'zh_TW' => '帳變歷史', 'th' => 'ประวัติธุรกรรม', 'ja' => '取引履歴', 'ko' => '거래 내역'], sort: 1)]
    public function history(int $id, MemberWalletRequest $request): JsonResponse
    {
        $page = $this->service->history($id, $request->validated());

        return apiPaginate($page, MemberWalletTransactionResource::collection($page));
    }

    #[MenuAction(['en' => 'Daily statistics', 'zh_CN' => '日统计', 'zh_TW' => '日統計', 'th' => 'สถิติรายวัน', 'ja' => '日次統計', 'ko' => '일일 통계'], sort: 2)]
    public function statistics(int $id, MemberWalletRequest $request): JsonResponse
    {
        $page = $this->service->statistics($id, $request->validated());

        return apiPaginate($page, MemberWalletDailyStatResource::collection($page));
    }

    #[MenuAction(['en' => 'Options', 'zh_CN' => '加载选项', 'zh_TW' => '載入選項', 'th' => 'ตัวเลือก', 'ja' => 'オプション', 'ko' => '옵션'], sort: 3)]
    public function options(MemberWalletRequest $request): JsonResponse
    {
        return apiSuccess($this->service->options($request->validated()));
    }

    #[MenuAction(['en' => 'Credit', 'zh_CN' => '入账', 'zh_TW' => '入帳', 'th' => 'รายเข้า', 'ja' => '入金', 'ko' => '입금'], sort: 4)]
    #[RequireSecurityPassword(mode: 'dialog')]
    public function credit(int $id, MemberWalletRequest $request): JsonResponse
    {
        return apiSuccess(new MemberWalletTransactionResource($this->service->credit($id, $request->validated())));
    }

    #[MenuAction(['en' => 'Debit', 'zh_CN' => '出账', 'zh_TW' => '出帳', 'th' => 'รายจ่าย', 'ja' => '出金', 'ko' => '출금'], sort: 5)]
    #[RequireSecurityPassword(mode: 'dialog')]
    public function debit(int $id, MemberWalletRequest $request): JsonResponse
    {
        return apiSuccess(new MemberWalletTransactionResource($this->service->debit($id, $request->validated())));
    }
}
