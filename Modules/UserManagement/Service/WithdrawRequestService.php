<?php

namespace Modules\UserManagement\Service;


use App\Service\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\TransactionManagement\Traits\TransactionTrait;
use Modules\UserManagement\Repository\WithdrawMethodRepositoryInterface;
use Modules\UserManagement\Repository\WithdrawRequestRepositoryInterface;
use Modules\UserManagement\Service\Interface\WithdrawRequestServiceInterface;

class WithdrawRequestService extends BaseService implements WithdrawRequestServiceInterface
{
    use TransactionTrait;

    protected $withdrawRequestRepository;

    public function __construct(WithdrawRequestRepositoryInterface $withdrawRequestRepository)
    {
        parent::__construct($withdrawRequestRepository);
        $this->withdrawRequestRepository = $withdrawRequestRepository;
    }

    public function update(int|string $id, array $data = []): ?Model
    {
        $withdrawRequest = $this->withdrawRequestRepository->findOne(id: $id, relations: ['user' => []]);
        $attributes = [
            'column' => 'id',
            'is_approved' => $data['is_approved'],
        ];
        if (array_key_exists('rejection_cause', $data) && !is_null($data['rejection_cause'])) {
            $attributes['rejection_cause'] = $data['rejection_cause'];
        }
        DB::beginTransaction();
        if ($data['is_approved'] == 0) {
            $this->withdrawRequestCancelTransaction($withdrawRequest?->user, $withdrawRequest?->amount, $withdrawRequest);
        } else {
            $this->withdrawRequestAcceptTransaction($withdrawRequest?->user, $withdrawRequest?->amount, $withdrawRequest);
        }
        $withdrawRequest = $this->withdrawRequestRepository->update(id: $id, data: $attributes);
        DB::commit();
        if ($data['is_approved'] == 0) {
            sendDeviceNotification(fcm_token: $withdrawRequest?->user->fcm_token,
                title: translate('withdraw_request_rejected'),
                description: translate(('admin_has_rejected_your_withdraw_request' . ($withdrawRequest?->rejection_cause != null ? ', because ' . $withdrawRequest?->rejection_cause : ' .'))),
                action: 'withdraw_rejected',
                user_id: $withdrawRequest?->user->id
            );
        } else {
            sendDeviceNotification(fcm_token: $withdrawRequest?->user->fcm_token,
                title: translate('withdraw_request_approved'),
                description: translate('admin_has_approved_your_withdraw_request'),
                action: 'withdraw_approved',
                user_id: $withdrawRequest?->user->id
            );
        }
        return $withdrawRequest;
    }

}
