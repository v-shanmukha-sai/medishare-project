/* MedShare mock data - replace with PHP/MySQL fetch later */
const USERS = {
  admin:       { name: 'System Admin',     role: 'Admin' },
  distributor: { name: 'MedPlus Distributors', role: 'Distributor' },
  ngo:         { name: 'Hope Orphanage',   role: 'NGO' }
};

const MEDICINES = [
  { id: 1,  name: 'Paracetamol 500mg',         cat: 'Pain Relief',   dist: 'MedPlus Distributors', price: 2.50,  stock: 1200, expiry: '2026-12-15', shelf: '4 months' },
  { id: 2,  name: 'Amoxicillin 250mg',         cat: 'Antibiotic',    dist: 'MedPlus Distributors', price: 4.75,  stock: 600,  expiry: '2026-11-02', shelf: '3 months' },
  { id: 3,  name: 'Cetirizine 10mg',           cat: 'Allergy',       dist: 'PharmaLink Co.',       price: 1.25,  stock: 800,  expiry: '2027-01-20', shelf: '5 months' },
  { id: 4,  name: 'Metformin 500mg',           cat: 'Diabetes',      dist: 'PharmaLink Co.',       price: 3.10,  stock: 450,  expiry: '2026-10-30', shelf: '3 months' },
  { id: 5,  name: 'Vitamin D3 60K IU',         cat: 'Supplements',   dist: 'MedPlus Distributors', price: 2.00,  stock: 300,  expiry: '2027-03-11', shelf: '7 months' },
  { id: 6,  name: 'ORS Sachets (20x21.5g)',    cat: 'Gastro',        dist: 'CareFirst Pharma',     price: 1.90,  stock: 950,  expiry: '2026-11-28', shelf: '4 months' },
  { id: 7,  name: 'Amoxicillin 250mg',         cat: 'Antibiotic',    dist: 'CareFirst Pharma',     price: 4.50,  stock: 210,  expiry: '2026-09-25', shelf: '2 months' },
  { id: 8,  name: 'Ibuprofen 400mg',           cat: 'Pain Relief',   dist: 'PharmaLink Co.',       price: 2.20,  stock: 0,    expiry: '2026-12-05', shelf: '4 months' }
];

const ORDERS = [
  { id: 101, med: 'Paracetamol 500mg', qty: 200, total: 500.00, buyer: 'Hope Orphanage', date: '2026-07-25', status: 'Delivered' },
  { id: 102, med: 'Amoxicillin 250mg', qty: 100, total: 475.00, buyer: 'City Care Hospital', date: '2026-07-28', status: 'Shipped' },
  { id: 103, med: 'ORS Sachets',       qty: 150, total: 285.00, buyer: 'Hope Orphanage', date: '2026-07-30', status: 'Pending' }
];

const NOTIFICATIONS = [
  { text: 'Amoxicillin 250mg expires in 2 months (CareFirst Pharma)', date: '2026-07-31', type: 'expiry' },
  { text: 'Order #103 placed by Hope Orphanage', date: '2026-07-30', type: 'order' },
  { text: 'New distributor "CareFirst Pharma" awaiting verification', date: '2026-07-29', type: 'verify' }
];

const AUDIT_LOGS = [
  { user: 'System Admin', action: 'Verified distributor: CareFirst Pharma', date: '2026-07-29' },
  { user: 'MedPlus Distributors', action: 'Added medicine: Vitamin D3 60K IU', date: '2026-07-26' },
  { user: 'Hope Orphanage', action: 'Placed order #103', date: '2026-07-30' }
];

const PENDING_VERIFICATIONS = [
  { name: 'CareFirst Pharma', type: 'Distributor', docs: 'License.pdf', date: '2026-07-29' },
  { name: 'Elder Care Home',  type: 'Old-Age Home', docs: 'RegCert.pdf', date: '2026-07-30' }
];
