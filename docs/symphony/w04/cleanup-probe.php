<?php
require __DIR__.'/runtime-bootstrap.php';
use Modules\Raonslab\TravelLab\Models\Inquiry;
use Modules\Raonslab\TravelLab\Services\InquiryService;
use Modules\Raonslab\TravelLab\Enums\InquiryStatus;
travelLabCheck(is_file(dirname(__DIR__,3).'/storage/framework/testing/w04-final-install/BLOCKED'),'Fresh TEST recovery boundary must remain held.');
w04App();
$count=0;
foreach (Inquiry::where('idempotency_key','like','w04-%')->get() as $inquiry) {
    travelLabCheck(str_starts_with($inquiry->user->email,'w04-') && str_ends_with($inquiry->user->email,'@example.invalid'),'Probe cleanup actor boundary failed.');
    if (!in_array($inquiry->status,[InquiryStatus::CANCELLED,InquiryStatus::DECLINED],true)) { app(InquiryService::class)->cancel($inquiry->user_id,$inquiry->id); ++$count; }
}
echo json_encode(['own_probe_inquiries_cancelled'=>$count])."\n";
