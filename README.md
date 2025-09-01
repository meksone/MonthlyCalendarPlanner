# MK Monthly Calendar Planner

A simple but powerful WordPress plugin to create and display monthly calendars with events.

## Description

This plugin allows you to create customizable monthly calendars and add event items to any day. It's perfect for planning content, scheduling events, or organizing any monthly activity. The calendars can be easily displayed on the frontend of your site using a simple shortcode.

The plugin also features a detailed styling options page, allowing you to customize the appearance of your calendars to perfectly match your theme.

## How to Use

### 1. Creating a Calendar

1.  Navigate to **Monthly Calendars** -> **All Calendars** in your WordPress admin menu.
2.  Click **Add New**.
3.  Give your calendar a title (e.g., "October 2025 Content Plan").
4.  In the "Calendar Settings & Items" section, select the **Month** and **Year** for your calendar.
5.  You can now add items to your calendar:
    *   **Calendar View:** Simply click the "Add Item" button at the bottom of any day cell.
    *   **Table View:** Click the `+` button in any cell to add an item.
6.  Click on an item's title bar to expand it and edit its title and text content.
7.  Drag and drop items to reorder them or move them to different days.
8.  When you're finished, click **Publish**.

### 2. Using Templates

You can create reusable event items called Templates.

1.  Navigate to **Monthly Calendars** -> **Templates**.
2.  Click **Add New**.
3.  Give the template a name for your reference (e.g., "Standard Blog Post").
4.  Fill in the "Item Title" and "Item Text" that you want to reuse.
5.  Publish the template.

Now, when you are editing a calendar, you will see your saved templates in the "Item Templates" sidebar on the right. Simply drag a template onto a day to add it as a new item.

### 3. Displaying on Your Site

To display a calendar on any page or post, use the following shortcode:

`[monthly_calendar id="123"]`

Replace `"123"` with the ID of your calendar post. You can find the post ID by looking at the URL when you edit the calendar (it will be `post=123`).

### 4. Customizing the Appearance

Navigate to **Monthly Calendars** -> **Styling**. On this page, you will find a comprehensive set of options to customize the colors, borders, spacing, and fonts for every part of your calendar's frontend display.

### 5. Admin Actions

On the main list of calendars and templates (**Monthly Calendars** -> **All Calendars/Templates**), you will find two new actions when you hover over an item:

*   **Duplicate:** Creates a new, draft copy of the item and all its settings. This is useful for quickly creating a new calendar based on an existing one.
*   **Delete Revisions:** Safely removes all saved revisions for an item, which can help keep your database clean. You will be asked to confirm before anything is deleted.

---
Thank you for using MK Monthly Calendar Planner!
