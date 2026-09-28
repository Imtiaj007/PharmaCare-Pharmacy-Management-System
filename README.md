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
      <img src="https://github.com/user-attachments/assets/0f365e64-bfb9-415b-ba37-e9d1ed760b3e" alt="Admin Login" />
    </td>
    <td width="33%">
      <h4 align="center">POS / Billing Management</h4>
      <img src="https://github.com/user-attachments/assets/dd412e0a-2503-4434-95da-b736d30b6387" alt="POS Billing" />
    </td>
    <td width="33%">
      <h4 align="center">Invoice & Receipt Generation</h4>
      <img src="https://github.com/user-attachments/assets/399ecbd8-145d-407e-af8f-91f3c0143c4d" />
    </td>
  </tr>
  <tr>
    <td width="33%">
      <h4 align="center">Inventory & Stock Tracking</h4>
      <img src="https://github.com/user-attachments/assets/7e0ddd95-3708-413a-8176-03b9534b8c68" />
    </td>
    <td width="33%">
      <h4 align="center">Staff Directory</h4>
      <img  src="https://github.com/user-attachments/assets/185533ae-bc86-4d22-b125-e6c373a4d420" />
    </td>
    <td width="33%">
      <h4 align="center">Customer Records</h4>
      <img src="https://github.com/user-attachments/assets/22087513-aecd-42ef-9624-a4fb0d349db5" />
    </td>
  </tr>
  <tr>
    <td width="33%">
      <h4 align="center">Supplier Directory</h4>
      <img  src="https://github.com/user-attachments/assets/150f8797-461d-4b8c-bc4d-3c1ecd9eb407" />
    </td>
    <td width="33%">
      <h4 align="center">Prescription Database</h4>
      <img src="https://github.com/user-attachments/assets/aabf9f69-846b-437d-8bda-e9f3551d5263" />
    </td>
    <td width="33%">
      <h4 align="center">Printable Invoice View</h4>
      <img src="https://github.com/user-attachments/assets/89de60b5-46ae-4bb8-937e-a678d926dc7a" />
    </td>
  </tr>
</table>

---

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
