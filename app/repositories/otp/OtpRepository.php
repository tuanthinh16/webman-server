<?php

namespace app\repositories\otp;

use app\model\Otp;

class OtpRepository
{

    protected $modelClass = Otp::class;

    public function getByUserID(int $userId)
    {
        return ($this->modelClass)::where('user_id', $userId)->first();
    }
    public function create(array $data)
    {
        try {
            return ($this->modelClass)::create($data);
        } catch (\Throwable $e) {
            throw new \Exception('Error creating OTP: ' . $e->getMessage());
        }
    }
    public function update(int $userId, array $data)
    {
        try {
            return ($this->modelClass)::where('user_id', $userId)->update($data);
        } catch (\Throwable $e) {
            throw new \Exception('Error updating OTP: ' . $e->getMessage());
        }
    }
    public function delete(int $userId)
    {
        try {
            return ($this->modelClass)::where('user_id', $userId)->delete();
        } catch (\Throwable $e) {
            throw new \Exception('Error deleting OTP: ' . $e->getMessage());
        }
    }
    public function markAsUsed(int $id)
    {
        try {
            $otp = ($this->modelClass)::find($id);
            if (!$otp) {
                throw new \Exception('OTP not found');
            }
            $otp->is_used = true;
            return $otp->save();
        } catch (\Throwable $e) {
            throw new \Exception('Error marking OTP as used: ' . $e->getMessage());
        }
    }
    public function findByUserIdAndOtp(int $userId, string $otp, $type)
    {
        return ($this->modelClass)::where('user_id', $userId)
            ->where('otp_code', $otp)
            ->where('is_used', false)
            ->where('valid_time', '>', date('Y-m-d H:i:s'))
            ->where('type', $type)
            ->first();
    }
}
