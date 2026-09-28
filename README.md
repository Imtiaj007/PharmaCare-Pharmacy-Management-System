# 💊 PharmaCare - Pharmacy Management System

PharmaCare is a comprehensive Relational Database Management System (RDBMS) web application designed to streamline pharmacy operations, inventory tracking, batch management, and sales transactions.

---

## 🚀 Key Features

* **Database Architecture:** Fully normalized (up to **3NF**) database schema with 8 interconnected tables.
* **Inventory & Stock Tracking:** Monitor batch numbers, stock quantities, and expiry dates.
* **Sales & Billing System:** Manage customer purchases and generate transaction records.
* **Complex Data Analytics:** Optimized SQL logic using multi-table JOINs, subqueries, and aggregation for business insights.

---

## 🛠️ Tech Stack

* **Database:** MySQL / phpMyAdmin
* **Backend:** PHP
* **Frontend:** HTML5, CSS3, JavaScript
* **Environment:** XAMPP Local Server

---
## 🖼️ Application Screenshots

<table>
  <tr>
    <td width="33%">
      <h4 align="center">Admin Login</h4>
      <!-- <img width="1917" height="908" alt="Screenshot 2026-09-28 225751" src="https://github.com/user-attachments/assets/0f365e64-bfb9-415b-ba37-e9d1ed760b3e" />
 -->
    </td>
    <td width="33%">
      <h4 align="center">POS / Billing Management</h4>
      <!-- <img width="1917" height="916" alt="Screenshot 2026-09-28 225807" src="https://github.com/user-attachments/assets/dd412e0a-2503-4434-95da-b736d30b6387" />
-->
    </td>
    <td width="33%">
      <h4 align="center">Invoice & Receipt Generation</h4>
      <!-- <img width="1912" height="885" alt="Screenshot 2026-09-28 230600" src="https://github.com/user-attachments/assets/7c169db8-263b-425c-87cb-7a5269ed3749" />
 -->
    </td>
  </tr>
  <tr>
    <td width="33%">
      <h4 align="center">Inventory & Stock Tracking</h4>
      <!-- <img width="1917" height="911" alt="Screenshot 2026-09-28 230609" src="https://github.com/user-attachments/assets/99cdf7e7-6e02-4f31-8ab8-fae854d958b5" />
 -->
    </td>
    <td width="33%">
      <h4 align="center">Staff Directory</h4>
      <!-- <img width="1917" height="917" alt="Screenshot 2026-09-28 230622" src="https://github.com/user-attachments/assets/d99d2f86-4ac1-4f4c-8c55-bc53196fa96e" />
৫ নম্বর ছবি এখানে ছাড়ুন -->
    </td>
    <td width="33%">
      <h4 align="center">Customer Records</h4>
      <!-- <img width="1917" height="912" alt="Screenshot 2026-09-28 230421" src="https://github.com/user-attachments/assets/967304eb-99ef-4e73-adba-d309bbfe61fd" />
৬ নম্বর ছবি এখানে ছাড়ুন -->
    </td>
  </tr>
  <tr>
    <td width="33%">
      <h4 align="center">Supplier Directory</h4>
      <!-- <img width="1915" height="880" alt="Screenshot 2026-09-28 230459" src="https://github.com/user-attachments/assets/9a96fa25-ab85-4f3b-b27f-85919881f0ea" />
৭ নম্বর ছবি এখানে ছাড়ুন -->
    </td>
    <td width="33%">
      <h4 align="center">Prescription Database</h4>
      <!--<img width="1917" height="908" alt="Screenshot 2026-09-28 230509" src="https://github.com/user-attachments/assets/fdd12ef1-ba82-4032-82dc-f015e5d67857" />
 ৮ নম্বর ছবি এখানে ছাড়ুন -->
    </td>
    <td width="33%">
      <h4 align="center">Printable Invoice View</h4>
      <!-- ৯<img width="1917" height="920" alt="Screenshot 2026-09-28 230517" src="https://github.com/user-attachments/assets/7fcf8a92-695f-4ccf-b50d-c3c374a7ee3b" />
 নম্বর ছবি এখানে ছাড়ুন -->
    </td>
  </tr>
</table>

## 📁 Database Schema

The system relies on 8 core relational tables:
1. `Employee` - Stores staff details and roles.
2. `Supplier` - Manages medicine suppliers.
3. `Medicine` - Stores drug details and categories.
4. `Batch` - Tracks stock quantity, prices, and expiry dates.
5. `Customer` - Records customer contact details.
6. `Prescription` - Links doctor prescriptions to customers.
7. `Sale` - Handles overall transaction records.
8. `SaleDetails` - Stores itemized sale information per batch.

---

## 💻 How to Run the Project Locally

Anyone can set up and run this project on their local machine by following these steps:

### **Prerequisites**
* Install **XAMPP Server** (Apache + MySQL) from [apachefriends.org](https://www.apachefriends.org/).

### **Step-by-Step Installation Guide**

1. **Clone or Download the Repository:**
   * Click on the green **`<Code>`** button on GitHub and select **Download ZIP**, or clone it using Git:
     ```bash
     git clone [https://github.com/Imtiaj007/PharmaCare-Pharmacy-Management-System.git](https://github.com/Imtiaj007/PharmaCare-Pharmacy-Management-System.git)
     ```

2. **Place the Files in the Web Directory:**
   * Extract/Move the project folder into your XAMPP `htdocs` directory:
     * Path: `C:\xampp\htdocs\PharmaCare-Pharmacy-Management-System`

3. **Start the Local Server:**
   * Open **XAMPP Control Panel**.
   * Click **Start** for both **Apache** and **MySQL**.

4. **Import the Database:**
   * Open your web browser and go to `http://localhost/phpmyadmin`.
   * Create a new database named `pharmacy` (or the database name specified in `db.php`).
   * Click on the **Import** tab.
   * Choose the `.sql` database backup file included in this repository and click **Import**.

5. **Run the Application:**
   * Open your browser and navigate to:
     ```text
     http://localhost/PharmaCare-Pharmacy-Management-System/
     ```
   *(If you renamed the folder to `pharmacy`, access it via `http://localhost/pharmacy/`)*

---

## 👨‍💻 Author

**Md. Emtiuj Ahmed Eipty**  
Department of Computer Science & Engineering  
Southeast University

---
