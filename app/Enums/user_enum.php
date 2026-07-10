<?php
namespace App\Enums;
enum UserRole: string
{
    case DOCTOR = 'doctor';
    case SUPPLIER = 'supplier';
    case ADMIN = 'admin';
} ;



enum UserStatus :string
{

 case ACTIVE =  'active';
 case INACTIVE = 'inactive';
  case SUSPENDED =   'suspended';
   case PENDING = 'pending';
}
?>
