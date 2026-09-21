<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
#[Fillable(['attendance_id','user_id','employee_id','school_id','attendance_date','event_type','event_status','checked_at','rejection_code','rejection_reason','is_mock_location','mock_detection_source','latitude','longitude','gps_accuracy','is_inside_geofence','selfie_path','device_info','geofence_version','client_geofence_version'])]
class AttendanceEvent extends Model
{
 protected function casts(): array { return ['attendance_date'=>'date','checked_at'=>'datetime','is_mock_location'=>'boolean','is_inside_geofence'=>'boolean','latitude'=>'decimal:7','longitude'=>'decimal:7','gps_accuracy'=>'decimal:2','geofence_version'=>'integer','client_geofence_version'=>'integer']; }
}
