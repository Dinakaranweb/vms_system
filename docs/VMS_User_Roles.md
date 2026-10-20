# VMS User Roles

**Vendor Management System — VMRF-DU**
Reference as of 20 October 2026. Companion image: `VMS_User_Roles.png`.

The system has **seven user types**. Each one has its own login area and its own set of
permissions; nobody shares a dashboard with anybody else. Six are internal to the university,
one (Vendor) is external.

| # | User type | Role name in the system | Login area | Permissions | Accounts today |
|---|-----------|------------------------|-----------|------------:|---------------:|
| 1 | Super Admin | `super-admin` | `/super-admin` | 47 | 1 |
| 2 | Administrator | `admin` | `/admin` | 26 | 1 |
| 3 | Tender Owner | `tender-owner` | `/tender-owner` | 20 | 2 |
| 4 | Purchase Committee | `purchase-committee` | `/purchase-committee` | 4 | 3 |
| 5 | Committee Member | `committee-member` | `/committee` | 0 (per tender) | 4 |
| 6 | Management | `management` | `/management` | 2 | 4 |
| 7 | Vendor | `vendor` | `/vendor` | 5 | 10 |

A user holds exactly one role, and the role decides which area they land in after signing in.

---

## 1. Super Admin

**Who it is:** the system owner. One account.

**What it is for:** running the system itself, not the day-to-day procurement. The Super Admin
holds every permission in the system (47 of 47), so it can do anything the other roles can —
but its real purpose is the configuration that nobody else can reach.

**Only the Super Admin can:**

- Create and manage user accounts, and assign roles
- Build roles and change what each role is allowed to do
- Appoint and reorder the standing **Purchase Committee**, and reset members' passwords
- Rearrange the sidebar menu for every role (menu builder)
- Build dashboard widgets and choose who sees them
- Read the **audit log** of who did what
- Change system-wide settings

**Also does:** everything an Administrator does.

---

## 2. Administrator

**Who it is:** the Purchase Officer in day-to-day practice. One account
(`purchaseofficer@vmu.edu.in`).

**What it is for:** running tenders end to end.

**Can:**

- Create, edit, publish and close tenders for the whole university
- Approve or reject vendor registrations, and edit vendor records
- Review, shortlist and reject applications
- Open a closed tender, which issues the committee's codes, and then collect those codes
- Record the documents received by post, and enter bid values from the quotes
- **Finalize the award** — choose the applicant and record the discussion with the committee
- Issue the contract and the work or purchase order, and sign on the university's side
- Run reports and export data
- Upload and manage tender documents

**Cannot:** create users, change roles, appoint the Purchase Committee, or see the audit log.
Those stay with the Super Admin.

---

## 3. Tender Owner

**Who it is:** a department or unit that runs its own tenders — for example the Digital Library.
Two accounts.

**What it is for:** the same work as an Administrator, but **limited to its own tenders**. This is
the whole point of the role: the permissions are named `… own tenders`, not `… tenders`, so a
Tender Owner never sees another department's procurement.

**Can, for its own tenders only:**

- Create, edit, publish and close them
- Shortlist, reject and award the applications received
- Open the tender and collect the committee's codes
- Record received documents and enter bid values
- Finalize the award and issue the contract
- Run reports and export data on its own tenders
- Appoint a per-tender evaluation committee

**Cannot:** touch tenders it does not own, approve vendor registrations, or manage users.

---

## 4. Purchase Committee

**Who it is:** the standing committee that witnesses tender openings — in practice the Registrar,
Pro Vice-Chancellor and Finance Officer. Three accounts, appointed by the Super Admin and held in
an ordered list.

**What it is for:** making sure no single officer can open a tender or award it unwitnessed. This
is the smallest permission set in the system (4 permissions) and the most consequential.

**Can:**

- **Hold an opening code.** When a tender is opened, each member receives their own six-digit
  code in their own login. The member reads it out to the officer in person; the officer enters
  every member's code on one screen. The tender does not open until the last code is in, so one
  person cannot open a tender alone.
- **Read the submitted documents** once the tender is open — the online uploads, the scanned
  postal submissions, or both.
- **Compare the bids** side by side.
- **Record a recommendation** on any applicant, with an optional position: support the award,
  oppose it, record no objection, or leave a remark with no position at all. These
  recommendations are **advisory** — the officer sees the tally but is not bound by it.
- **Enter postal bid data** and record the documents received.

**Cannot:** finalize an award or issue a contract. The decision belongs to the Purchase Officer.
The one committee act that still blocks an award outright is a formal recorded rejection.

---

## 5. Committee Member

**Who it is:** a subject expert added to **one specific tender** to evaluate it. Four accounts.

**What it is for:** per-tender technical evaluation, as opposed to the standing Purchase
Committee's role at the opening. A Committee Member is attached to a tender through its committee
list, with a designation and their own permissions on that tender.

**Can:**

- See only the tenders they were added to
- Read and evaluate the applications on those tenders

This role carries **no system-wide permissions at all** — its access comes entirely from being
named on a tender. If nobody adds them to a tender, they see nothing.

> **Note:** this is separate from the Purchase Committee above, and the two are easy to confuse.
> Purchase Committee = standing, holds opening codes, appointed once. Committee Member = per
> tender, evaluates that tender only.

---

## 6. Management

**Who it is:** the Vice-Chancellor, Pro Vice-Chancellor, Registrar and Finance Officer in their
oversight capacity. Four accounts.

**What it is for:** visibility without the ability to change anything. Management holds exactly
two permissions, and neither of them modifies a tender.

**Can:**

- View every tender in the university, with dashboards and counts by status and submission mode
- Raise a query on a tender and read the reply

**Cannot:** create, edit, publish, close or award anything. This is deliberately a read-and-ask
role.

---

## 7. Vendor

**Who it is:** the external supplier. Ten accounts, each linked to a vendor company record.

**What it is for:** the only user type outside the university, and the only one that submits
applications.

**Can:**

- Register, then wait for an Administrator to approve the company
- Browse published tenders, filtered by online, postal or hybrid
- Answer the eligibility questions, and apply if the answers qualify
- Submit a bid amount, technical and financial proposals, and supporting documents — for a
  postal tender, send the papers by post to the address given instead
- Track their own applications and read their own notifications
- Read and sign their own contract

**Cannot:** see another vendor's application or bid, or see any tender before it is published.
A vendor may apply to a given tender only once; a second attempt is refused by the database.

---

## How the roles meet

The seven roles are not a hierarchy. They divide one job four ways, and the division is what
makes the audit trail work:

| Stage | Who acts |
|-------|----------|
| Create and publish a tender | Administrator, or Tender Owner for its own |
| Apply | Vendor |
| Open the sealed tender | Purchase Committee's codes, collected by the officer |
| Evaluate | Committee Member (per tender) and Purchase Committee (advisory) |
| Decide the award | Administrator or Tender Owner, on the record |
| Issue and sign the contract | Administrator or Tender Owner, then Vendor |
| Watch the whole thing | Management (read only), Super Admin (audit log) |

Two points in that chain stop until a person acts: the **opening codes**, which need every
committee member, and the **award finalization**, which needs the officer to record the chosen
applicant and the discussion held with the committee.

---

## A note on the Super Admin account

Because the Super Admin holds all 47 permissions, it can perform any procurement action itself —
create a tender, open it, finalize the award and issue the contract — without involving anyone
else. That is intended for recovery and setup, not for routine use, and the audit log is what
makes such use visible after the fact. Day-to-day procurement should run through the
Administrator and Tender Owner accounts so the separation of duties above actually holds.

---

# The accounts today

All 25 accounts on the system as of 20 October 2026. Every user holds exactly one role.

## Super Admin — 1 account

| ID | Name | Email |
|---:|------|-------|
| 1 | Super Admin | `superadmin@example.com` |

## Administrator — 1 account

| ID | Name | Email |
|---:|------|-------|
| 2 | Mr.Manikandan | `purchaseofficer@vmu.edu.in` |

## Tender Owner — 2 accounts

| ID | Name | Email |
|---:|------|-------|
| 6 | test@gmail.com | `test@gmail.com` |
| 7 | Dr. Nanthini R O — Digital Library | `vmrfdl@vmu.edu.in` |

## Purchase Committee — 3 accounts

These are the accounts that receive the opening codes. All three are active.

| ID | Name | Email | Designation | Order |
|---:|------|-------|-------------|------:|
| 25 | Mr.Nagappan | `info@sriinnov.com` | Registrar | 1 |
| 26 | Provc | `provc@gg.com` | Pvc | 2 |
| 27 | Ss | `ss@gmail.com` | Finance | 3 |

## Committee Member — 4 accounts

| ID | Name | Email |
|---:|------|-------|
| 9 | Committee Demo | `committee.demo@example.com` |
| 13 | Dr. Kavitha Suresh | `kavitha.suresh.demo.041268@example.com` |
| 15 | Dr. Kavitha Suresh | `kavitha.suresh.demo.826015@example.com` |
| 18 | Dr. Kavitha Suresh | `kavitha.suresh.demo.194991@example.com` |

## Management — 4 accounts

| ID | Name | Email |
|---:|------|-------|
| 20 | Vice Chancellor | `vc@vmu.edu.in` |
| 21 | Pro Vice Chancellor | `pvc@vmu.edu.in` |
| 22 | Registrar | `registrar@vmu.edu.in` |
| 23 | Finance Officer | `financeofficer@vmu.edu.in` |

## Vendor — 10 accounts

| ID | Name | Email | Company | Status |
|---:|------|-------|---------|--------|
| 3 | Vendor User | `vendor@example.com` | Tech Solutions Pvt Ltd | approved |
| 4 | Sanjai I | `sanjai.vmu@gmail.com` | SAN Industries | approved |
| 5 | Dinakaran | `innovcomp@gmail.com` | Sri innov technologies | approved |
| 8 | Nanthini test 1 | `violetsfun@gmail.com` | DL testing 1 | approved |
| 10 | Tilak | `srithilakram@gmail.com` | Abc | approved |
| 11 | Arjun Mehta | `brightpath.demo.788684@example.com` | BrightPath Digital Supplies | approved |
| 14 | Arjun Mehta | `chennai.library.demo.826015@example.com` | Chennai Library Suppliers Pvt Ltd | **pending** |
| 16 | Arjun Mehta | `chennai.library.demo.844269@example.com` | Chennai Library Suppliers Pvt Ltd | **pending** |
| 17 | Arjun Mehta | `chennai.library.demo.194991@example.com` | Chennai Library Suppliers 194991 | approved |
| 19 | raja | `sriinnovapple@gmail.com` | SIT | approved |

Vendors 14 and 16 are still **pending approval**. They can sign in but cannot apply to any
tender until an Administrator approves the company.

---

# Tidying up before go-live

Nothing below has been done. It is a plan, with the ownership of each account checked against
the database first, because deleting an account that owns a tender or an application breaks the
record rather than cleaning it.

## 1. Fix the Super Admin address — do this first

Account 1 runs on `superadmin@example.com`, a reserved placeholder domain that can never receive
mail. Password resets and every system notification to the one account holding all 47
permissions will bounce silently.

It owns no tenders, applications or committee records, so **changing its email is safe** and
breaks nothing. Point it at a real monitored `@vmu.edu.in` mailbox.

## 2. Give the Purchase Committee real identities

| Account | Problem |
|---------|---------|
| 26 — "Provc" | `provc@gg.com` — an external throwaway domain |
| 27 — "Ss" | `ss@gmail.com` — personal address, two-letter name |
| 25 — "Mr.Nagappan" | `info@sriinnov.com` — a company mailbox, not a person |

These three accounts receive the one-time codes that open every sealed tender. A code sent to a
shared company inbox or a personal address weakens the control the committee exists to provide.
Move all three to named university addresses and set their full names and designations.

All three hold live opening codes and are named on past openings, so **edit these accounts in
place — do not delete and recreate them**, or the audit trail of who opened what is lost.

## 3. Deal with the `test@gmail.com` tender owner

Account 6 has `test@gmail.com` as both its name and its email, and it **owns eight published
tenders**:

`TDR-2026-0004` · `TDR-2026-0005` · `TDR-2026-0006` · `TDR-2026-0007` · `TDR-2026-0012`
· `TDR-2026-0019` · `TDR-2026-0020` · `TDR-2026-0022`

Five are plainly scratch work (`kkjhkjhjkh`, `b`, `text5`, `sample`, `Test4`); three look like
generated demo data (`Library Vendor Rate Contract 826015`, and similar). All eight are
`published`, so they are visible to vendors right now.

Two options, in order of safety:

1. **Keep the account, rename it.** Give it the real department name and address, then close or
   delete the eight scratch tenders individually. Nothing is orphaned.
2. **Delete the account.** Only after those eight tenders are dealt with — an owner cannot be
   removed while tenders point at it.

## 4. Remove the duplicate demo accounts

These were created by repeated demo seeding. Each was checked: **none owns a tender, an
application, a review comment or an opening code**, so deleting them removes nothing real.

| ID | Role | Email |
|---:|------|-------|
| 9 | committee-member | `committee.demo@example.com` |
| 13 | committee-member | `kavitha.suresh.demo.041268@example.com` |
| 15 | committee-member | `kavitha.suresh.demo.826015@example.com` |
| 18 | committee-member | `kavitha.suresh.demo.194991@example.com` |
| 11 | vendor | `brightpath.demo.788684@example.com` |
| 14 | vendor | `chennai.library.demo.826015@example.com` |
| 16 | vendor | `chennai.library.demo.844269@example.com` |
| 17 | vendor | `chennai.library.demo.194991@example.com` |

That removes all four Committee Member accounts, which is correct: the three "Dr. Kavitha Suresh"
rows are the same fictional person seeded three times, and no real per-tender evaluator has been
appointed yet. Appoint real ones when a tender actually needs technical evaluation.

## 5. Leave this one alone for now

Account 3, `vendor@example.com` (Tech Solutions Pvt Ltd), holds four applications — but all four
are on the `TEST-HYB-*` and `TEST-POST-*` tenders seeded for testing the opening flow. Clear
those test tenders first and the applications go with them; then the account can be removed too.

## After the tidy

| | Now | After |
|---|----:|------:|
| Accounts | 25 | 17 |
| Committee Member accounts | 4 | 0 (appoint per tender) |
| Vendors | 10 | 6 |
| Addresses that cannot receive mail | 8 | 0 |
