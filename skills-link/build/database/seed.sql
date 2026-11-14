DELETE FROM users WHERE email LIKE '%@skillslink.local' OR email IN ('sparkling@clean.co.za', 'greenthumb@gardencentre.co.za', 'swiftmove@logistics.co.za', 'oceanview@hotels.co.za', 'baobab@freshmarket.co.za', 'highveld@industrial.co.za', 'bushveld@lodge.co.za', 'diamondfields@supermarket.co.za', 'tablebay@stores.co.za');

INSERT INTO users (email, password_hash, role, is_verified, popia_consent_at) VALUES
('sparkling@clean.co.za', '$2y$10$abcdefghijklmnopqrstuurnDlmWHw/nbon/yzvXfeAbN8a34/cUy', 'recruiter', 1, NOW()),
('greenthumb@gardencentre.co.za', '$2y$10$abcdefghijklmnopqrstuurnDlmWHw/nbon/yzvXfeAbN8a34/cUy', 'recruiter', 1, NOW()),
('swiftmove@logistics.co.za', '$2y$10$abcdefghijklmnopqrstuurnDlmWHw/nbon/yzvXfeAbN8a34/cUy', 'recruiter', 1, NOW()),
('oceanview@hotels.co.za', '$2y$10$abcdefghijklmnopqrstuurnDlmWHw/nbon/yzvXfeAbN8a34/cUy', 'recruiter', 1, NOW()),
('baobab@freshmarket.co.za', '$2y$10$abcdefghijklmnopqrstuurnDlmWHw/nbon/yzvXfeAbN8a34/cUy', 'recruiter', 1, NOW()),
('highveld@industrial.co.za', '$2y$10$abcdefghijklmnopqrstuurnDlmWHw/nbon/yzvXfeAbN8a34/cUy', 'recruiter', 1, NOW()),
('bushveld@lodge.co.za', '$2y$10$abcdefghijklmnopqrstuurnDlmWHw/nbon/yzvXfeAbN8a34/cUy', 'recruiter', 1, NOW()),
('diamondfields@supermarket.co.za', '$2y$10$abcdefghijklmnopqrstuurnDlmWHw/nbon/yzvXfeAbN8a34/cUy', 'recruiter', 1, NOW()),
('tablebay@stores.co.za', '$2y$10$abcdefghijklmnopqrstuurnDlmWHw/nbon/yzvXfeAbN8a34/cUy', 'recruiter', 1, NOW()),
('worker1@skillslink.local', '$2y$10$abcdefghijklmnopqrstuurnDlmWHw/nbon/yzvXfeAbN8a34/cUy', 'jobseeker', 1, NOW()),
('worker2@skillslink.local', '$2y$10$abcdefghijklmnopqrstuurnDlmWHw/nbon/yzvXfeAbN8a34/cUy', 'jobseeker', 1, NOW()),
('worker3@skillslink.local', '$2y$10$abcdefghijklmnopqrstuurnDlmWHw/nbon/yzvXfeAbN8a34/cUy', 'jobseeker', 1, NOW()),
('worker4@skillslink.local', '$2y$10$abcdefghijklmnopqrstuurnDlmWHw/nbon/yzvXfeAbN8a34/cUy', 'jobseeker', 1, NOW()),
('worker5@skillslink.local', '$2y$10$abcdefghijklmnopqrstuurnDlmWHw/nbon/yzvXfeAbN8a34/cUy', 'jobseeker', 1, NOW());

INSERT INTO recruiter_profiles (user_id, company_name, registration_number, company_description, address, city, province_id, zip_code, website, contact_first_name, contact_last_name, contact_position, verification_status, verified_at)
SELECT u.id, c.company, c.reg, c.descr, c.addr, c.city, (SELECT id FROM provinces WHERE name = c.province), c.zip, c.web, c.fn, c.ln, c.pos, 'verified', NOW()
FROM users u JOIN (
SELECT 'sparkling@clean.co.za' AS email, 'Sparkling Clean & Secure' AS company, '2018/204511/07' AS reg, 'Thandiwe' AS fn, 'Mokoena' AS ln, 'Operations Manager' AS pos, 'Commercial cleaning and security guarding for offices and warehouses in the Eastern Cape.' AS descr, '12 Govan Mbeki Avenue' AS addr, 'Gqeberha' AS city, 'Eastern Cape' AS province, '6001' AS zip, 'www.clean.co.za' AS web
UNION ALL SELECT 'greenthumb@gardencentre.co.za' AS email, 'Green Thumb Garden Centre' AS company, '2016/318240/07' AS reg, 'Pieter' AS fn, 'van der Merwe' AS ln, 'Owner' AS pos, 'Garden centres and hardware supplies in Bloemfontein and Welkom.' AS descr, '45 Nelson Mandela Drive' AS addr, 'Bloemfontein' AS city, 'Free State' AS province, '9301' AS zip, 'www.gardencentre.co.za' AS web
UNION ALL SELECT 'swiftmove@logistics.co.za' AS email, 'Swift Move Logistics' AS company, '2015/246810/07' AS reg, 'Sipho' AS fn, 'Dlamini' AS ln, 'HR Manager' AS pos, 'Warehousing, packing and truck deliveries for retailers in Johannesburg and Pretoria.' AS descr, '88 Main Reef Road' AS addr, 'Johannesburg' AS city, 'Gauteng' AS province, '2000' AS zip, 'www.logistics.co.za' AS web
UNION ALL SELECT 'oceanview@hotels.co.za' AS email, 'Ocean View Hotels' AS company, '2018/135792/07' AS reg, 'Priya' AS fn, 'Naidoo' AS ln, 'Hotel Manager' AS pos, 'Beachfront hotels and guest houses in Durban and the Midlands.' AS descr, '230 Marine Parade' AS addr, 'Durban' AS city, 'KwaZulu-Natal' AS province, '4001' AS zip, 'www.hotels.co.za' AS web
UNION ALL SELECT 'baobab@freshmarket.co.za' AS email, 'Baobab Fresh Market' AS company, '2016/112233/07' AS reg, 'Mpho' AS fn, 'Maluleke' AS ln, 'Branch Manager' AS pos, 'Supermarkets serving Polokwane and Thohoyandou.' AS descr, '15 Thabo Mbeki Street' AS addr, 'Polokwane' AS city, 'Limpopo' AS province, '0700' AS zip, 'www.freshmarket.co.za' AS web
UNION ALL SELECT 'highveld@industrial.co.za' AS email, 'Highveld Industrial Services' AS company, '2014/445566/07' AS reg, 'Johan' AS fn, 'Botha' AS ln, 'Site Manager' AS pos, 'Industrial cleaning and delivery services around Secunda and Emalahleni.' AS descr, '7 Kruger Street' AS addr, 'Secunda' AS city, 'Mpumalanga' AS province, '2302' AS zip, 'www.industrial.co.za' AS web
UNION ALL SELECT 'bushveld@lodge.co.za' AS email, 'Bushveld Lodge & Resort' AS company, '2020/778899/07' AS reg, 'Kagiso' AS fn, 'Molefe' AS ln, 'Lodge Manager' AS pos, 'Resort and lodge near Rustenburg and Pilanesberg.' AS descr, '21 Kloof Road' AS addr, 'Rustenburg' AS city, 'North West' AS province, '0299' AS zip, 'www.lodge.co.za' AS web
UNION ALL SELECT 'diamondfields@supermarket.co.za' AS email, 'Diamond Fields Supermarket' AS company, '2013/998877/07' AS reg, 'Anika' AS fn, 'Visser' AS ln, 'Store Manager' AS pos, 'Supermarkets in Kimberley and Upington.' AS descr, '60 Du Toitspan Road' AS addr, 'Kimberley' AS city, 'Northern Cape' AS province, '8301' AS zip, 'www.supermarket.co.za' AS web
UNION ALL SELECT 'tablebay@stores.co.za' AS email, 'Table Bay Stores' AS company, '2021/314159/07' AS reg, 'Lindiwe' AS fn, 'Jacobs' AS ln, 'Director' AS pos, 'Family stores with in-store cafes in Cape Town and Stellenbosch.' AS descr, '9 Long Street' AS addr, 'Cape Town' AS city, 'Western Cape' AS province, '8001' AS zip, 'www.stores.co.za' AS web
) c ON c.email = u.email;

INSERT INTO jobs (recruiter_id, category_id, title, description, province_id, city, employment_type, pay_amount, pay_period, below_nmw_flag, status)
VALUES
((SELECT id FROM users WHERE email = 'sparkling@clean.co.za'), (SELECT id FROM job_categories WHERE name = 'Cleaner'), 'Office Cleaner', 'Clean offices, kitchens and bathrooms in the Gqeberha CBD. Cleaning materials are supplied.', (SELECT id FROM provinces WHERE name = 'Eastern Cape'), 'Gqeberha', 'part_time', 32, 'hourly', 0, 'open'),
((SELECT id FROM users WHERE email = 'sparkling@clean.co.za'), (SELECT id FROM job_categories WHERE name = 'Security Guard'), 'Security Guard (Night Shift)', 'Guard a site in East London. PSIRA registration is needed. 12 hour night shifts.', (SELECT id FROM provinces WHERE name = 'Eastern Cape'), 'East London', 'full_time', 6500, 'monthly', 0, 'open'),
((SELECT id FROM users WHERE email = 'greenthumb@gardencentre.co.za'), (SELECT id FROM job_categories WHERE name = 'Gardener'), 'Garden Centre Assistant', 'Water plants, help customers and keep the garden centre tidy in Bloemfontein.', (SELECT id FROM provinces WHERE name = 'Free State'), 'Bloemfontein', 'part_time', 350, 'daily', 0, 'open'),
((SELECT id FROM users WHERE email = 'greenthumb@gardencentre.co.za'), (SELECT id FROM job_categories WHERE name = 'General Worker'), 'General Worker', 'Help with loading, cleaning and general work at a hardware store in Welkom.', (SELECT id FROM provinces WHERE name = 'Free State'), 'Welkom', 'full_time', 1500, 'weekly', 0, 'open'),
((SELECT id FROM users WHERE email = 'swiftmove@logistics.co.za'), (SELECT id FROM job_categories WHERE name = 'Warehouse Worker'), 'Warehouse Packer', 'Pick and pack orders at a distribution centre in Johannesburg. Must be able to lift 20kg.', (SELECT id FROM provinces WHERE name = 'Gauteng'), 'Johannesburg', 'full_time', 7200, 'monthly', 0, 'open'),
((SELECT id FROM users WHERE email = 'swiftmove@logistics.co.za'), (SELECT id FROM job_categories WHERE name = 'Loader/Offloader'), 'Loader / Offloader', 'Load and offload trucks at a depot in Pretoria. Safety boots are provided.', (SELECT id FROM provinces WHERE name = 'Gauteng'), 'Pretoria', 'full_time', 1700, 'weekly', 0, 'open'),
((SELECT id FROM users WHERE email = 'oceanview@hotels.co.za'), (SELECT id FROM job_categories WHERE name = 'Waiter/Waitress'), 'Waiter / Waitress', 'Serve tables at a hotel restaurant on the Durban beachfront. Weekend work is required.', (SELECT id FROM provinces WHERE name = 'KwaZulu-Natal'), 'Durban', 'part_time', 35, 'hourly', 0, 'open'),
((SELECT id FROM users WHERE email = 'oceanview@hotels.co.za'), (SELECT id FROM job_categories WHERE name = 'Housekeeper'), 'Hotel Housekeeper', 'Clean and prepare guest rooms at a hotel in Pietermaritzburg.', (SELECT id FROM provinces WHERE name = 'KwaZulu-Natal'), 'Pietermaritzburg', 'full_time', 5800, 'monthly', 0, 'open'),
((SELECT id FROM users WHERE email = 'baobab@freshmarket.co.za'), (SELECT id FROM job_categories WHERE name = 'Cashier'), 'Cashier', 'Work the till at a supermarket in Polokwane. Basic maths and good customer service needed.', (SELECT id FROM provinces WHERE name = 'Limpopo'), 'Polokwane', 'full_time', 6000, 'monthly', 0, 'open'),
((SELECT id FROM users WHERE email = 'baobab@freshmarket.co.za'), (SELECT id FROM job_categories WHERE name = 'Packer'), 'Shelf Packer', 'Pack and stack shelves at a supermarket in Thohoyandou.', (SELECT id FROM provinces WHERE name = 'Limpopo'), 'Thohoyandou', 'full_time', 5900, 'monthly', 0, 'open'),
((SELECT id FROM users WHERE email = 'highveld@industrial.co.za'), (SELECT id FROM job_categories WHERE name = 'Cleaner'), 'Industrial Cleaner', 'Clean offices and workshops at a plant site near Secunda. Safety training is provided.', (SELECT id FROM provinces WHERE name = 'Mpumalanga'), 'Secunda', 'full_time', 6200, 'monthly', 0, 'open'),
((SELECT id FROM users WHERE email = 'highveld@industrial.co.za'), (SELECT id FROM job_categories WHERE name = 'Driver Assistant'), 'Delivery Driver Assistant', 'Ride along on a delivery truck and help with loading and offloading.', (SELECT id FROM provinces WHERE name = 'Mpumalanga'), 'Emalahleni', 'full_time', 1600, 'weekly', 0, 'open'),
((SELECT id FROM users WHERE email = 'bushveld@lodge.co.za'), (SELECT id FROM job_categories WHERE name = 'Kitchen Assistant'), 'Kitchen Assistant', 'Wash dishes, prepare vegetables and keep the kitchen clean at a resort near Rustenburg.', (SELECT id FROM provinces WHERE name = 'North West'), 'Rustenburg', 'full_time', 5900, 'monthly', 0, 'open'),
((SELECT id FROM users WHERE email = 'bushveld@lodge.co.za'), (SELECT id FROM job_categories WHERE name = 'Handyman'), 'Resort Handyman', 'Do small repairs and general maintenance at a resort in Pilanesberg.', (SELECT id FROM provinces WHERE name = 'North West'), 'Pilanesberg', 'part_time', 40, 'hourly', 0, 'open'),
((SELECT id FROM users WHERE email = 'diamondfields@supermarket.co.za'), (SELECT id FROM job_categories WHERE name = 'Shop Assistant'), 'Shop Assistant', 'Help customers, stack shelves and keep the store clean in Kimberley.', (SELECT id FROM provinces WHERE name = 'Northern Cape'), 'Kimberley', 'part_time', 27, 'hourly', 1, 'open'),
((SELECT id FROM users WHERE email = 'diamondfields@supermarket.co.za'), (SELECT id FROM job_categories WHERE name = 'Cashier'), 'Cashier', 'Work the till at a supermarket in Upington. Good customer service needed.', (SELECT id FROM provinces WHERE name = 'Northern Cape'), 'Upington', 'full_time', 5800, 'monthly', 0, 'open'),
((SELECT id FROM users WHERE email = 'tablebay@stores.co.za'), (SELECT id FROM job_categories WHERE name = 'Shop Assistant'), 'Store Assistant', 'Help customers and keep shelves full at a store in Cape Town.', (SELECT id FROM provinces WHERE name = 'Western Cape'), 'Cape Town', 'part_time', 35, 'hourly', 0, 'open'),
((SELECT id FROM users WHERE email = 'tablebay@stores.co.za'), (SELECT id FROM job_categories WHERE name = 'Kitchen Assistant'), 'Cafe Kitchen Assistant', 'Prepare food and keep the kitchen clean at an in-store cafe in Stellenbosch.', (SELECT id FROM provinces WHERE name = 'Western Cape'), 'Stellenbosch', 'full_time', 5900, 'monthly', 0, 'open');

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 5, 'Paid on time and treated me well.'
FROM jobs j JOIN users w ON w.email = 'worker1@skillslink.local'
WHERE j.title = 'Office Cleaner' AND j.city = 'Gqeberha';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 4, 'Fair pay and a good team.'
FROM jobs j JOIN users w ON w.email = 'worker2@skillslink.local'
WHERE j.title = 'Office Cleaner' AND j.city = 'Gqeberha';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 5, 'Very friendly and clear about the work.'
FROM jobs j JOIN users w ON w.email = 'worker3@skillslink.local'
WHERE j.title = 'Office Cleaner' AND j.city = 'Gqeberha';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 4, 'Fair pay and a good team.'
FROM jobs j JOIN users w ON w.email = 'worker2@skillslink.local'
WHERE j.title = 'Security Guard (Night Shift)' AND j.city = 'East London';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 4, 'Mostly good, hours were sometimes long.'
FROM jobs j JOIN users w ON w.email = 'worker3@skillslink.local'
WHERE j.title = 'Security Guard (Night Shift)' AND j.city = 'East London';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 5, 'Very friendly and clear about the work.'
FROM jobs j JOIN users w ON w.email = 'worker3@skillslink.local'
WHERE j.title = 'Garden Centre Assistant' AND j.city = 'Bloemfontein';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 5, 'Paid on time and treated me well.'
FROM jobs j JOIN users w ON w.email = 'worker4@skillslink.local'
WHERE j.title = 'Garden Centre Assistant' AND j.city = 'Bloemfontein';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 4, 'Fair pay and a good team.'
FROM jobs j JOIN users w ON w.email = 'worker5@skillslink.local'
WHERE j.title = 'Garden Centre Assistant' AND j.city = 'Bloemfontein';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 3, 'Average experience overall.'
FROM jobs j JOIN users w ON w.email = 'worker4@skillslink.local'
WHERE j.title = 'General Worker' AND j.city = 'Welkom';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 4, 'Fair pay and a good team.'
FROM jobs j JOIN users w ON w.email = 'worker5@skillslink.local'
WHERE j.title = 'General Worker' AND j.city = 'Welkom';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 4, 'Fair pay and a good team.'
FROM jobs j JOIN users w ON w.email = 'worker5@skillslink.local'
WHERE j.title = 'Warehouse Packer' AND j.city = 'Johannesburg';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 3, 'Average experience overall.'
FROM jobs j JOIN users w ON w.email = 'worker1@skillslink.local'
WHERE j.title = 'Warehouse Packer' AND j.city = 'Johannesburg';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 5, 'Paid on time and treated me well.'
FROM jobs j JOIN users w ON w.email = 'worker2@skillslink.local'
WHERE j.title = 'Warehouse Packer' AND j.city = 'Johannesburg';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 5, 'Very friendly and clear about the work.'
FROM jobs j JOIN users w ON w.email = 'worker1@skillslink.local'
WHERE j.title = 'Loader / Offloader' AND j.city = 'Pretoria';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 5, 'Paid on time and treated me well.'
FROM jobs j JOIN users w ON w.email = 'worker2@skillslink.local'
WHERE j.title = 'Loader / Offloader' AND j.city = 'Pretoria';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 4, 'Good employer, the work was as described.'
FROM jobs j JOIN users w ON w.email = 'worker2@skillslink.local'
WHERE j.title = 'Waiter / Waitress' AND j.city = 'Durban';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 5, 'Great employer, I would work for them again.'
FROM jobs j JOIN users w ON w.email = 'worker3@skillslink.local'
WHERE j.title = 'Waiter / Waitress' AND j.city = 'Durban';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 3, 'It was okay, but communication could be better.'
FROM jobs j JOIN users w ON w.email = 'worker4@skillslink.local'
WHERE j.title = 'Waiter / Waitress' AND j.city = 'Durban';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 3, 'Average experience overall.'
FROM jobs j JOIN users w ON w.email = 'worker3@skillslink.local'
WHERE j.title = 'Hotel Housekeeper' AND j.city = 'Pietermaritzburg';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 2, 'Work was different from what was advertised.'
FROM jobs j JOIN users w ON w.email = 'worker4@skillslink.local'
WHERE j.title = 'Hotel Housekeeper' AND j.city = 'Pietermaritzburg';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 4, 'Good employer, the work was as described.'
FROM jobs j JOIN users w ON w.email = 'worker5@skillslink.local'
WHERE j.title = 'Hotel Housekeeper' AND j.city = 'Pietermaritzburg';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 5, 'Very friendly and clear about the work.'
FROM jobs j JOIN users w ON w.email = 'worker4@skillslink.local'
WHERE j.title = 'Cashier' AND j.city = 'Polokwane';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 4, 'Good employer, the work was as described.'
FROM jobs j JOIN users w ON w.email = 'worker5@skillslink.local'
WHERE j.title = 'Cashier' AND j.city = 'Polokwane';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 4, 'Good employer, the work was as described.'
FROM jobs j JOIN users w ON w.email = 'worker5@skillslink.local'
WHERE j.title = 'Shelf Packer' AND j.city = 'Thohoyandou';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 4, 'Fair pay and a good team.'
FROM jobs j JOIN users w ON w.email = 'worker1@skillslink.local'
WHERE j.title = 'Shelf Packer' AND j.city = 'Thohoyandou';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 5, 'Great employer, I would work for them again.'
FROM jobs j JOIN users w ON w.email = 'worker1@skillslink.local'
WHERE j.title = 'Industrial Cleaner' AND j.city = 'Secunda';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 5, 'Very friendly and clear about the work.'
FROM jobs j JOIN users w ON w.email = 'worker2@skillslink.local'
WHERE j.title = 'Industrial Cleaner' AND j.city = 'Secunda';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 4, 'Good employer, the work was as described.'
FROM jobs j JOIN users w ON w.email = 'worker3@skillslink.local'
WHERE j.title = 'Industrial Cleaner' AND j.city = 'Secunda';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 3, 'Average experience overall.'
FROM jobs j JOIN users w ON w.email = 'worker2@skillslink.local'
WHERE j.title = 'Delivery Driver Assistant' AND j.city = 'Emalahleni';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 4, 'Good employer, the work was as described.'
FROM jobs j JOIN users w ON w.email = 'worker3@skillslink.local'
WHERE j.title = 'Delivery Driver Assistant' AND j.city = 'Emalahleni';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 4, 'Fair pay and a good team.'
FROM jobs j JOIN users w ON w.email = 'worker4@skillslink.local'
WHERE j.title = 'Delivery Driver Assistant' AND j.city = 'Emalahleni';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 4, 'Good employer, the work was as described.'
FROM jobs j JOIN users w ON w.email = 'worker3@skillslink.local'
WHERE j.title = 'Kitchen Assistant' AND j.city = 'Rustenburg';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 5, 'Great employer, I would work for them again.'
FROM jobs j JOIN users w ON w.email = 'worker4@skillslink.local'
WHERE j.title = 'Kitchen Assistant' AND j.city = 'Rustenburg';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 5, 'Great employer, I would work for them again.'
FROM jobs j JOIN users w ON w.email = 'worker4@skillslink.local'
WHERE j.title = 'Resort Handyman' AND j.city = 'Pilanesberg';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 4, 'Mostly good, hours were sometimes long.'
FROM jobs j JOIN users w ON w.email = 'worker5@skillslink.local'
WHERE j.title = 'Resort Handyman' AND j.city = 'Pilanesberg';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 4, 'Good employer, the work was as described.'
FROM jobs j JOIN users w ON w.email = 'worker1@skillslink.local'
WHERE j.title = 'Resort Handyman' AND j.city = 'Pilanesberg';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 4, 'Mostly good, hours were sometimes long.'
FROM jobs j JOIN users w ON w.email = 'worker5@skillslink.local'
WHERE j.title = 'Shop Assistant' AND j.city = 'Kimberley';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 3, 'Average experience overall.'
FROM jobs j JOIN users w ON w.email = 'worker1@skillslink.local'
WHERE j.title = 'Shop Assistant' AND j.city = 'Kimberley';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 4, 'Fair pay and a good team.'
FROM jobs j JOIN users w ON w.email = 'worker2@skillslink.local'
WHERE j.title = 'Shop Assistant' AND j.city = 'Kimberley';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 5, 'Great employer, I would work for them again.'
FROM jobs j JOIN users w ON w.email = 'worker2@skillslink.local'
WHERE j.title = 'Store Assistant' AND j.city = 'Cape Town';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 5, 'Very friendly and clear about the work.'
FROM jobs j JOIN users w ON w.email = 'worker3@skillslink.local'
WHERE j.title = 'Store Assistant' AND j.city = 'Cape Town';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 5, 'Paid on time and treated me well.'
FROM jobs j JOIN users w ON w.email = 'worker4@skillslink.local'
WHERE j.title = 'Store Assistant' AND j.city = 'Cape Town';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 2, 'Payment was late more than once.'
FROM jobs j JOIN users w ON w.email = 'worker3@skillslink.local'
WHERE j.title = 'Cafe Kitchen Assistant' AND j.city = 'Stellenbosch';

INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
SELECT j.id, w.id, j.recruiter_id, 3, 'It was okay, but communication could be better.'
FROM jobs j JOIN users w ON w.email = 'worker4@skillslink.local'
WHERE j.title = 'Cafe Kitchen Assistant' AND j.city = 'Stellenbosch';

UPDATE recruiter_profiles p
SET avg_rating = COALESCE((SELECT ROUND(AVG(r.rating), 2) FROM reviews r WHERE r.reviewee_user_id = p.user_id), 0),
    rating_count = (SELECT COUNT(*) FROM reviews r WHERE r.reviewee_user_id = p.user_id)
WHERE p.user_id IN (SELECT id FROM users WHERE email IN ('sparkling@clean.co.za', 'greenthumb@gardencentre.co.za', 'swiftmove@logistics.co.za', 'oceanview@hotels.co.za', 'baobab@freshmarket.co.za', 'highveld@industrial.co.za', 'bushveld@lodge.co.za', 'diamondfields@supermarket.co.za', 'tablebay@stores.co.za'));