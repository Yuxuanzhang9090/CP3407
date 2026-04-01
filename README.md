# CP3407
# Online Food Ordering and Delivery System
## 📊 Project Management

### GitHub Repository

Project link:  
https://github.com/Yuxuanzhang9090/CP3407  

---

### Team Members
**Zhang Yuxuan**  
**Chong Yuan Xin**  
**Thura Aung**  

## 📌 Project Overview

This project aims to design and develop a web-based **online food ordering and delivery system**.  
It allows users to easily browse restaurants, place orders, make secure payments, and track delivery progress in real time.

Unlike basic food ordering websites, this system simulates a **real-world food delivery platform** by integrating multiple roles, including customers, restaurants, and delivery riders.

The system supports a complete workflow:
> **Browse Menu → Add to Cart → Place Order → Payment → Delivery Tracking**

This project demonstrates how modern web technologies can be used to build a practical and scalable system similar to platforms such as **GrabFood** and **Foodpanda**.

---

## 🎯 Project Objectives

The main objectives of this project are:

- Build a simple and user-friendly website for food ordering  
- Allow users to easily browse menus and place orders  
- Support secure online payments using **Stripe**  
- Implement a full workflow:  
  > Order Placement → Processing → Delivery  
- Help restaurants efficiently receive and manage orders  
- Enable customers to track order status in real time  
- Design a system similar to real-world food delivery platforms  
- Build a system that can be extended with future features  

---

## 📦 System Scope

### Included Scope

- Restaurant and menu browsing  
- Shopping cart and checkout system  
- Order submission and storage  
- Online payment integration (Stripe)  
- Order status tracking  
- Order history  
- Basic rider assignment logic  
- Database design and system integration  

### Future Enhancements (Not Included)

- Real-time GPS tracking  
- Live rider location updates  

---

## ⚙️ Main Features

### Customer Module

- Browse restaurants and menus  
- View food details (price, description)  
- Add items to cart and manage quantity  
- Enter delivery details (address, phone, notes)  
- Place orders through checkout  
- Make secure payments via Stripe  
- View order history  
- Track order status in real time  

### Restaurant Module

- Receive customer orders  
- View order details (items, quantity, delivery info)  
- Update order status (e.g., preparing, ready for pickup)  

### Order Management System

- Create and store orders in database  
- Record order items and quantities  
- Calculate total price, delivery fee, and service fee  
- Manage order status lifecycle  
- Retrieve order history  

### Payment System (Stripe Integration)

- Create secure checkout sessions  
- Redirect users to Stripe payment page  
- Handle payment confirmation  
- Store payment data (session ID, payment status)  
- Display payment result on success page  

### Order Tracking System

- Display current order status  
- Show progress stages:
  - Order placed  
  - Preparing  
  - Delivering  
  - Delivered  
- Provide a track order page  
- Support order history tracking  

---

## 🛠️ Technology Stack

- Frontend: HTML, CSS, JavaScript  
- Backend: PHP  
- Database: MySQL  
- Server: XAMPP  
- Payment: Stripe API  
- Version Control: GitHub  

---

## 🏗️ System Architecture

This system follows a three-tier architecture:

### Presentation Layer (Frontend)
Handles user interaction and displays information via web pages.

### Application Layer (Backend)
Handles system logic such as order creation, payment processing, and status updates.

### Data Layer (Database)
Stores all data including users, menu items, orders, and transactions.

This structure makes the system easier to maintain and extend.

---

## 🔒 Data and Privacy

The system will handle user data such as names, contact details, and delivery addresses.  
All data will be stored securely and used only for order processing.

Sensitive payment information will not be stored in the system.  
All payments will be processed through Stripe to ensure security.

---

## 🚀 Expected Outcomes

By the end of the project, the system is expected to:

- Provide a complete food ordering process  
- Store order details for order history  
- Successfully integrate online payment functionality  
- Allow users to track their orders  
- Demonstrate a practical food delivery system structure  
- Show the ability to design and build a complete system  

---

## 📈 Future Improvements

- Real-time delivery tracking (GPS)  
- Rider dashboard  
- Restaurant dashboard  
- Admin panel  
- Promotions and discounts  
- Notification system  

## 🗂️ Project Plan

Online Food Ordering and Delivery System

This project aims to develop a realistic web-based online food ordering and delivery system. The system is designed to simulate the workflow of a real food delivery platform, where customers can browse restaurants, select menu items, place orders, make online payments, and track the delivery process.

Unlike a very basic food ordering website, our project is planned as a more complete platform that connects three main parties: customers, restaurants, and delivery riders. Therefore, the project will not only focus on ordering food, but also on payment handling, order management, rider assignment, and order tracking.

To make the project manageable and well-organized, the development process is divided into several phases.

---

### 📌 1. Planning and Requirement Analysis

In the first phase, the team will identify the main problem and define the core functions of the system. The main goal is to make food ordering more convenient for customers and more efficient for restaurants.

At this stage, the team will:

- decide the project scope and topic  
- identify the main users of the system  
- study how real food delivery websites work  
- list the core features that must be included in the prototype  

The team has already decided that the system should support:

- restaurant and menu browsing  
- shopping cart  
- order placement  
- secure online payment  
- order tracking  
- order history  
- restaurant-side order handling  
- rider-side delivery updates  

This phase is important because it provides a clear direction for the development of the entire system.

---

### ⚙️ 2. System Design

After identifying the requirements, the next step is to design the overall system structure. This includes database design, page structure, and workflow design.

The system is designed around a realistic food delivery process:

- The user browses restaurants and menus  
- The user adds items into the cart  
- The user confirms the order and enters delivery details  
- The system calculates the total amount, delivery fee, and service fee  
- The user completes payment through Stripe  
- The restaurant receives the order and prepares the food  
- A rider is assigned to deliver the order  
- The customer tracks the delivery status until the order is completed  

At this stage, the team will design:

- the page navigation flow  
- the ordering workflow  
- the payment workflow  
- the tracking workflow  
- the database tables for users, restaurants, menu items, orders, order items, riders, and payments  

Special attention will be given to designing the order-related tables properly, because order data is the core of the whole platform.

---

### 💻 3. Frontend Development

The frontend part of the system focuses on user interaction and page design. The aim is to make the website easy to use and similar to a real food delivery platform.

The main pages planned for development include:

- Home page / categories page  
- Restaurant listing page  
- Restaurant menu page  
- Cart page  
- Checkout / place order page  
- Payment page  
- Order success page  
- Track order page  
- My orders page  
- Login / register pages  

The frontend should clearly guide users through the ordering process. For example, users should be able to easily move from menu browsing to cart review, from cart to checkout, and from payment to order tracking.

In addition, the interface should provide clear status information, such as:

- order placed  
- preparing  
- out for delivery  
- delivered  

This helps improve the overall user experience.

---

### 🧠 4. Backend Development

The backend is one of the most important parts of this project because it handles the system logic and connects the frontend with the database.

The backend development will include:

- user authentication and session handling  
- cart management  
- order creation and storage  
- total price calculation  
- order item insertion  
- restaurant and rider assignment  
- payment processing integration  
- order status updates  
- order history retrieval  
- tracking information display  

For this project, PHP is used to handle the server-side logic, while MySQL is used to store and manage all system data.

The backend should ensure that:

- orders are stored correctly  
- food items in each order are recorded properly  
- payment status is updated correctly  
- users can retrieve their previous orders  
- tracking information reflects the latest order progress  

This phase is crucial because even if the frontend looks good, the website will not function properly without a strong backend structure.

---

### 💳 5. Payment Integration

A major feature of this project is secure online payment. Instead of only simulating a payment process, the team plans to integrate Stripe so that the website is closer to a real commercial platform.

The payment module will support:

- checkout session creation  
- redirection to Stripe payment page  
- payment confirmation  
- payment success display  
- storage of payment-related information in the database  

This part is especially valuable because it shows that the project goes beyond a simple academic demo. It demonstrates how real food delivery platforms process transactions securely.

The team also plans to structure the system in a way that supports future improvements, such as:

- storing Stripe session IDs  
- recording payment status  
- preparing for restaurant and rider payout logic  
- allowing the platform to calculate service fees and commission  

This makes the system more realistic and professionally designed.

---

### 🚚 6. Order Tracking and Delivery Workflow

To make the website more practical, order tracking is planned as an important system feature.

After an order is placed and paid, the customer should be able to check the progress of the order. The tracking function is expected to include several stages such as:

- order received  
- restaurant preparing food  
- rider assigned  
- on the way  
- delivered  

This feature is important because food delivery is not only about placing orders. Customers also want to know what is happening after payment.

The system will therefore include:

- a track order page  
- a my orders page  
- status update logic in the backend  
- the possibility of rider-side status updates  

This part makes the system feel much closer to a real food delivery website and improves the completeness of the project.

---

### 🧪 7. Testing and Debugging

Testing will be carried out throughout the development process to make sure that all parts of the system work correctly.

The team will test:

- whether users can browse menus correctly  
- whether cart functions work properly  
- whether order totals are calculated correctly  
- whether payment can be completed successfully  
- whether order records are saved in the database  
- whether track order and my orders pages show correct information  
- whether the website handles invalid input properly  

Testing is especially important for this project because the system involves many connected steps. If one part fails, the whole ordering process may be affected.

For example:

- if cart data is not stored correctly, checkout will fail  
- if the payment result is not recorded, the order cannot move to the next stage  
- if the tracking status is not updated, the customer cannot see delivery progress  

Therefore, debugging and system integration testing will be a major part of the plan.

---

### 🚀 8. Deployment and Demonstration Preparation

After development and testing, the final phase is system deployment and preparation for demonstration.

The system will be run in a local development environment using XAMPP. The team will prepare the project so that the lecturer can clearly see:

- the website interface  
- the ordering workflow  
- payment integration  
- order status updates  
- tracking functionality  
- overall system design  

The team will also prepare:

- GitHub repository for code sharing and version control  
- screenshots or demo scenarios  
- explanation of each team member’s contribution  
- possible future improvements  

This final step is important because a good project is not only about coding, but also about presenting the system clearly and professionally.