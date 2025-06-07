# User Manual - Rocket Production Management System

## 1. การเริ่มต้นใช้งาน

### 1.1 การเข้าสู่ระบบ (Login)

1. **เปิดเบราว์เซอร์** และไปที่ `http://localhost/testjules/public/`
2. **ป้อนข้อมูลการเข้าสู่ระบบ**:
   - Username: ชื่อผู้ใช้ที่ได้รับจากผู้ดูแลระบบ
   - Password: รหัสผ่าน
3. **คลิก "Login"** เพื่อเข้าสู่ระบบ

**ข้อมูลผู้ใช้ตัวอย่าง**:
- **Admin**: username: `admin`, password: `password`
- **Operator**: username: `operator1`, password: `password`

### 1.2 การออกจากระบบ (Logout)
1. คลิกที่ชื่อผู้ใช้งานมุมขวาบน
2. เลือก "Logout" จากเมนู dropdown
3. ระบบจะพาไปที่หน้า Login อัตโนมัติ

## 2. หน้า Dashboard

### 2.1 ภาพรวม Dashboard
หน้า Dashboard แสดงข้อมูลสำคัญของระบบ:

#### KPI Cards
- **Total Orders**: จำนวน Production Orders ทั้งหมด
- **Completed**: จำนวน Orders ที่เสร็จสิ้นแล้ว
- **In Progress**: จำนวน Orders ที่กำลังดำเนินการ
- **Pending**: จำนวน Orders ที่รอการดำเนินการ

#### Production Orders Table
แสดงรายการ Production Orders ล่าสุด พร้อมข้อมูล:
- Production Number
- Project Name
- Model Name
- Status
- Actions (View, Edit)

### 2.2 การใช้งาน Search และ Filter

#### Search Function
1. ป้อนคำค้นหาในช่อง "Search..."
2. ระบบจะค้นหาจาก:
   - Production Number
   - Project Name
   - Model Name
3. กด Enter หรือคลิกปุ่มค้นหา

#### Status Filter
1. เลือกสถานะจาก dropdown "Filter by Status"
2. เลือก:
   - **All**: แสดงทั้งหมด
   - **Pending**: รอการดำเนินการ
   - **In Progress**: กำลังดำเนินการ
   - **Completed**: เสร็จสิ้นแล้ว

## 3. การจัดการ Production Orders

### 3.1 การสร้าง Production Order ใหม่

1. **คลิกปุ่ม "Create New Order"** ที่หน้า Dashboard
2. **กรอกข้อมูลพื้นฐาน**:
   - **Production Number**: รหัสการผลิต (ห้ามซ้ำ)
   - **Empty Tube Number**: รหัสท่อเปล่า
   - **Project**: เลือกโครงการ
   - **Model**: เลือกรุ่น (จะแสดงตาม Project ที่เลือก)

3. **เพิ่มข้อมูล Liner Usage** (ถ้ามี):
   - คลิก "Add Liner"
   - กรอก Liner Type
   - กรอก Liner Batch Number
   - เพิ่มหมายเหตุ (ถ้าต้องการ)

4. **เพิ่ม Process Log** (ถ้ามี):
   - คลิก "Add Process Step"
   - กรอก Sequence Number
   - กรอก Process Step Name
   - เลือกวันที่ดำเนินการ
   - เลือกผลการทำงาน (Pass/Fail)
   - กรอกค่าควบคุมและค่าที่วัดได้จริง

5. **คลิก "Create Order"** เพื่อบันทึก

### 3.2 การดู Production Order

1. **จากหน้า Dashboard**: คลิกปุ่ม "View" ในแถวของ Order ที่ต้องการ
2. **หน้า View Order จะแสดง**:
   
   #### ข้อมูลพื้นฐาน
   - Production Number
   - Empty Tube Number
   - Project และ Model
   - สถานะปัจจุบัน
   - ผู้อนุมัติและวันที่อนุมัติ

   #### Liner Usage Table
   - Liner Type
   - Batch Number
   - หมายเหตุ

   #### Process Log Table
   - Sequence
   - Process Step Name
   - วันที่ดำเนินการ
   - ผลการทำงาน
   - ผู้ปฏิบัติงาน
   - ค่าควบคุม vs ค่าที่วัดได้
   - หมายเหตุ

### 3.3 การแก้ไข Production Order

1. **เข้าหน้า View Order**
2. **คลิกปุ่ม "Edit Order"**
3. **แก้ไขข้อมูลที่ต้องการ**:
   - ข้อมูลพื้นฐาน
   - Liner Usage
   - Process Log
4. **คลิก "Update Order"** เพื่อบันทึกการเปลี่ยนแปลง

#### การเพิ่ม/ลบ Liner Usage
- **เพิ่ม**: คลิก "Add Liner"
- **ลบ**: คลิกปุ่ม "Remove" ข้าง Liner ที่ต้องการลบ

#### การเพิ่ม/ลบ Process Log
- **เพิ่ม**: คลิก "Add Process Step"  
- **ลบ**: คลิกปุ่ม "Remove" ข้าง Process ที่ต้องการลบ

### 3.4 การอัพเดทสถานะ Order

1. **เข้าหน้า Edit Order**
2. **เลือกสถานะใหม่** จาก dropdown "MC02 Status":
   - **Pending**: รอการดำเนินการ
   - **In Progress**: กำลังดำเนินการ  
   - **Completed**: เสร็จสิ้น
3. **คลิก "Update Order"**

### 3.5 การอนุมัติ Order (สำหรับ Admin)

1. **เข้าหน้า Edit Order**
2. **เลือก "Sign Order"** (ถ้ามีสิทธิ์)
3. **ระบบจะบันทึก**:
   - ชื่อผู้อนุมัติ
   - วันเวลาที่อนุมัติ
4. **Order จะเปลี่ยนสถานะเป็น "Completed"**

## 4. การนำทางในระบบ

### 4.1 Navigation Menu
- **Dashboard**: หน้าหลักแสดงสถิติและรายการ Orders
- **Orders**: รายการ Production Orders ทั้งหมด
- **Projects**: การจัดการโครงการ (สำหรับ Admin)
- **Users**: การจัดการผู้ใช้งาน (สำหรับ Admin)

### 4.2 Breadcrumb Navigation
ใช้ breadcrumb ที่ด้านบนของหน้าเพื่อย้อนกลับไปหน้าก่อนหน้า
ตัวอย่าง: `Home > Orders > PO-2025-001`

### 4.3 Action Buttons
- **Primary Actions**: ปุ่มสีน้ำเงิน (Create, Update, Save)
- **Secondary Actions**: ปุ่มสีเทา (Cancel, Back)
- **Danger Actions**: ปุ่มสีแดง (Delete, Remove)

## 5. Tips การใช้งาน

### 5.1 การป้อนข้อมูล
- **Production Number**: ใช้รูปแบบ PO-YYYY-XXX (เช่น PO-2025-001)
- **วันที่**: ใช้รูปแบบ DD/MM/YYYY
- **ตัวเลข**: ใช้จุดทศนิยม เช่น 1.25, 10.5

### 5.2 การบันทึกข้อมูล
- กดปุ่ม "Save" หรือ "Update" เสมอหลังจากแก้ไขข้อมูล
- ระบบจะแสดงข้อความยืนยันเมื่อบันทึกสำเร็จ
- หากมีข้อผิดพลาด ระบบจะแสดงข้อความเตือนสีแดง

### 5.3 การค้นหาข้อมูล
- ใช้ wildcard สำหรับการค้นหา (เช่น "PO-2025*")
- ค้นหาได้ทั้งตัวอักษรใหญ่และเล็ก
- สามารถค้นหาบางส่วนของคำได้

### 5.4 การใช้งานบนมือถือ
- ระบบรองรับการใช้งานบนมือถือ
- หมุนหน้าจอเป็นแนวนอนสำหรับตารางข้อมูล
- ใช้นิ้วเลื่อนเพื่อดูตารางที่กว้าง

---

**หากต้องการความช่วยเหลือเพิ่มเติม**: ติดต่อทีมสนับสนุน  
**Email**: support@rocketprod.local  
**Manual Version**: 1.0
