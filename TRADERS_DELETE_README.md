# Deleting a Trader – Staff Guide

A simple guide for dashboard users. No technical knowledge needed.

---

## Two ways to remove a trader

| | **Delete** | **Force delete** |
|---|---|---|
| When to use | Trader was added by mistake and has nothing recorded | You must remove the trader **and everything about them** |
| What is removed | Only the trader | The trader, their purchase orders, payments, receipts and game accounts |
| Who can do it | Anyone allowed to manage traders | Only roles with the **Force Delete Traders** permission |
| Can it be undone? | No | No |

> **Not sure?** Don't delete. Edit the trader and set **Status** to **Inactive** instead. This hides the trader from new purchases and keeps all records safe.

---

## 1. Normal delete

Works **only** if the trader has never had any:

- purchase orders (including cancelled ones)
- game accounts
- payments (including cancelled ones)

**Steps**

1. Go to **Traders**.
2. Click **Delete** next to the trader (or open the trader and click **Delete**).
3. Confirm.

If the trader has any history, you'll see a message explaining why, for example *"Has 2 purchase order(s)."* Nothing is deleted.

---

## 2. Force delete (delete everything)

Use this only when you are sure, for example, to remove test data or a trader created completely by mistake.

**Steps**

1. Go to **Traders** and click **Delete** on the trader.
2. The message says the trader cannot be deleted. Click **Force delete everything**.
   *(If you don't see this button, you don't have permission. Ask an admin.)*
3. Read the summary. It shows how many purchase orders, payments and game accounts will be removed, and how many of those accounts were already sold.
4. **Type the trader's name exactly** as shown.
5. Write a **reason** (for example *"Test trader"*).
6. Click **Delete everything**.

### What gets deleted

- The trader
- All their purchase orders (including cancelled ones)
- All their payments and uploaded receipts
- All game accounts bought from them, **sold or unsold**

### What is kept

- **Customer sales stay.** Sales income is not lost, but those sales will no longer show which account was sold.
- **A record of the deletion.** Every force delete is saved in **System Ops → Activity log** with who did it, when, and the reason.

### Safety checks

- Nothing happens unless the typed name matches and a reason is given.
- Either everything is deleted or nothing is. It never stops halfway.

---

## Who can delete? (for admins)

Admins control this from **Roles & Permissions** → open a role → **Traders** section:

| Permission | Allows |
|---|---|
| **View Traders** | See traders and their history |
| **Manage Traders, Purchase Orders & Payments** | Add and edit traders, orders and payments; normal **Delete** |
| **Void Purchase Orders & Payments** | Cancel a single order or payment |
| **Force Delete Traders (with all their data)** | **Force delete everything** |

- Force delete needs **both** "Manage Traders…" **and** "Force Delete Traders" ticked.
- By default, **Admin** and **Account manager** have force delete. Untick it on a role to take it away.

---

## Quick decision guide

- Trader has **no history** → **Delete**.
- Trader has history, but you just want to stop using them → set them to **Inactive**.
- Trader has history and **must be wiped completely** → **Force delete everything**.
