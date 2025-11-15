<?php
//
//namespace Database\Seeders;
//
//use Illuminate\Database\Seeder;
//use Spatie\Permission\Models\Permission;
//use Spatie\Permission\Models\Role;
//
//class PermissionsTableSeeder extends Seeder
//{
//    /**
//     * Run the database seeds.
//     */
//    public function run(): void
//    {
//        // Clear cached roles and permissions
//        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
//
//        // Define all models and their CRUD operations
//        $models = [
//            'Role',
//            'Semester',
//            'Student',
//            'User',
//            'AttendanceLog',
//            'AttendanceStat'
//        ];
//
//        $operations = ['create', 'view', 'update', 'delete'];
//
//        // Create permissions for each model and operation
//        foreach ($models as $model) {
//            foreach ($operations as $operation) {
//                Permission::firstOrCreate([
//                    'name' => "{$operation} {$model}"
//                ]);
//            }
//        }
//
//        // Create additional permissions for specific pages if needed
//        $pagePermissions = [
//            'page_AttendanceLogSearch',
//            'page_AttendanceStats',
//            'access_admin_panel'
//        ];
//
//        foreach ($pagePermissions as $permission) {
//            Permission::firstOrCreate([
//                'name' => $permission
//            ]);
//        }
//
//        // Create admin role and assign all permissions
//        $adminRole = Role::firstOrCreate(['name' => 'admin']);
//        $adminRole->givePermissionTo(Permission::all());
//
//        // Create accountant role with financial and reporting permissions
//        $accountantRole = Role::firstOrCreate(['name' => 'accountant']);
//        $accountantRole->givePermissionTo([
//            // Student permissions
//            'view Student',
//
//            // Attendance permissions for reporting
//            'view AttendanceLog',
//            'view AttendanceStat',
//
//            // Page access
//            'page_AttendanceLogSearch',
//            'page_AttendanceStats',
//            'access_admin_panel'
//        ]);
//
//        // Create manager role with comprehensive management permissions
//        $managerRole = Role::firstOrCreate(['name' => 'manager']);
//        $managerRole->givePermissionTo([
//            // Student management
//            'view Student',
//            'create Student',
//            'update Student',
//
//            // Semester management
//            'view Semester',
//            'create Semester',
//            'update Semester',
//
//            // Attendance management
//            'view AttendanceLog',
//            'create AttendanceLog',
//            'update AttendanceLog',
//            'view AttendanceStat',
//
//            // User management (limited - no delete)
//            'view User',
//            'create User',
//            'update User',
//
//            // Page access
//            'page_AttendanceLogSearch',
//            'page_AttendanceStats',
//            'access_admin_panel'
//        ]);
//
//        $this->command->info('Permissions and roles seeded successfully!');
//        $this->command->info('Total permissions created: ' . Permission::count());
//        $this->command->info('Total roles created: ' . Role::count());
//
//        // Display role permissions summary
//        $this->command->info("\nRole Permissions Summary:");
//        $this->command->info("Admin: " . $adminRole->permissions->count() . " permissions");
//        $this->command->info("Manager: " . $managerRole->permissions->count() . " permissions");
//        $this->command->info("Accountant: " . $accountantRole->permissions->count() . " permissions");
//    }
//}
