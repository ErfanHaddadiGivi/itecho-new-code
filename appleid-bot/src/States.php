<?php

namespace AppleBot;

/**
 * نام حالت‌های ماشین حالت (state machine). انگلیسی و ثابت.
 */
final class States
{
    // مسیر مشتری
    public const START               = 'START';
    public const CHOOSING_WARRANTY   = 'CHOOSING_WARRANTY';
    public const CHOOSING_ICLOUD     = 'CHOOSING_ICLOUD';
    public const ENTERING_FIRST_NAME = 'ENTERING_FIRST_NAME';
    public const ENTERING_LAST_NAME  = 'ENTERING_LAST_NAME';
    public const ENTERING_PHONE      = 'ENTERING_PHONE';
    public const ENTERING_EMAIL      = 'ENTERING_EMAIL';
    public const ENTERING_BIRTHDATE  = 'ENTERING_BIRTHDATE';
    public const CONFIRMING_ORDER    = 'CONFIRMING_ORDER';
    public const AWAITING_RECEIPT    = 'AWAITING_RECEIPT';
    public const AWAITING_APPROVAL   = 'AWAITING_APPROVAL';
    public const AWAITING_CODE       = 'AWAITING_VERIFICATION_CODE';
    public const AWAITING_FINAL      = 'AWAITING_FINAL';
    public const COMPLETED           = 'COMPLETED';

    // حالت‌های ورودیِ ادمین (پاسخ متنی بعد از کلیک روی دکمه)
    public const ADMIN_REJECT_REASON      = 'ADMIN_REJECT_REASON';
    public const ADMIN_FINAL_CREDENTIALS  = 'ADMIN_FINAL_CREDENTIALS';
}
