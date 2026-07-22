<?php
declare(strict_types=1);
/**
 * Editable content fields for every public page.
 * Used by AdminCP → Website Pages (the editor form) and read on the
 * front-end via get_content($conn, page, key, default).
 *
 * Shape: page => ['label' => ..., 'icon' => ..., 'sections' => [
 *     section title => [key => [label, type(text|textarea), default]]
 * ]]
 */
return [

    'home' => [
        'label' => 'Home', 'icon' => 'fa-house',
        'sections' => [
            'Slider — Slide 1' => [
                'slide1_kicker' => ['Small badge text', 'text', 'Software Development Company'],
                'slide1_title'  => ['Heading (text between * * shows in gradient color)', 'textarea', 'We Build *Web, Mobile & Software* Solutions That Grow Your Business'],
                'slide1_text'   => ['Sub text', 'textarea', 'Malik Solution is a professional, highly skilled development company delivering modern websites, mobile apps, custom software, managed IT services, and reliable hosting to clients worldwide.'],
                'slide1_image'  => ['Background image', 'image', 'slide-1.jpg', '1920 × 900 px (wide landscape)'],
                'slide1_style'  => ['Slide style', 'select', 'text', ['text' => 'Text + dark overlay (default)', 'image' => 'Image only — full designed banner']],
            ],
            'Slider — Slide 2' => [
                'slide2_kicker' => ['Small badge text', 'text', 'Web Development'],
                'slide2_title'  => ['Heading (text between * * shows in gradient color)', 'textarea', 'Modern Websites That *Convert Visitors* Into Customers'],
                'slide2_text'   => ['Sub text', 'textarea', 'Interactive, robust, and easy-to-use web solutions that represent your business — from landing pages to complete e-commerce platforms.'],
                'slide2_image'  => ['Background image', 'image', 'Ser-bg1.jpg', '1920 × 900 px (wide landscape)'],
                'slide2_style'  => ['Slide style', 'select', 'text', ['text' => 'Text + dark overlay (default)', 'image' => 'Image only — full designed banner']],
            ],
            'Slider — Slide 3' => [
                'slide3_kicker' => ['Small badge text', 'text', 'Managed IT & Hosting'],
                'slide3_title'  => ['Heading (text between * * shows in gradient color)', 'textarea', '24/7 Monitoring, *Secure Hosting* & Expert IT Support'],
                'slide3_text'   => ['Sub text', 'textarea', 'We keep your IT systems running optimally so you can focus on growing your business — with cloud backup, disaster recovery, and hosting from Rs.1,999/yr.'],
                'slide3_image'  => ['Background image', 'image', 'Hero-Hosting.png', '1920 × 900 px (wide landscape)'],
                'slide3_style'  => ['Slide style', 'select', 'text', ['text' => 'Text + dark overlay (default)', 'image' => 'Image only — full designed banner']],
            ],
            'Stats Band (number | label)' => [
                'stat1' => ['Stat 1', 'text', '150+ | Projects Completed'],
                'stat2' => ['Stat 2', 'text', '30+ | Engineers On Staff'],
                'stat3' => ['Stat 3', 'text', '99.99% | Hosting Uptime'],
                'stat4' => ['Stat 4', 'text', '24/7 | Monitoring & Support'],
            ],
            'About Preview' => [
                'about_title' => ['Heading', 'text', 'One of the Fastest Growing Web & Software Development Companies'],
                'about_text'  => ['Paragraph', 'textarea', 'We craft high-performance websites, mobile apps, software, and e-commerce platforms for the healthcare, education, and business industries. From small startups to established corporations, our team delivers solutions worthy of outstanding business success.'],
            ],
            'Bottom Call-To-Action' => [
                'cta_title' => ['Heading', 'text', "Have a project in mind? Let's build it together."],
                'cta_text'  => ['Sub text', 'textarea', "Tell us your requirements and we'll deliver a web, mobile, or software solution worthy of outstanding business success."],
            ],
            'Images' => [
                'about_image' => ['About section image', 'image', 'Hero-About.png', '1200 × 700 px (landscape)'],
            ],
        ],
    ],

    'about' => [
        'label' => 'About Us', 'icon' => 'fa-circle-info',
        'sections' => [
            'Page Hero' => [
                'hero_title' => ['Heading', 'text', 'About Malik Solution'],
                'hero_lead'  => ['Sub text', 'textarea', 'One of the fastest growing web development companies — crafting high-performance websites, mobile apps, software, and e-commerce solutions.'],
            ],
            'Who We Are' => [
                'who_title' => ['Heading', 'text', 'One of the Fastest Growing Web Development Companies'],
                'who_text1' => ['Paragraph 1', 'textarea', 'Malik Solution Private Limited is one of the fastest growing web development companies. We craft high-performance Websites, Mobile Apps, Software, and E-Commerce platforms for the Healthcare, Education, and Business industries. Our development team always aims for satisfied customers — we work with a wide range of clients, from very small startups to established large corporations.'],
                'who_text2' => ['Paragraph 2', 'textarea', 'Over the years of experience, we have helped our clients build great web, mobile & software solutions. With endless possibilities, all you need is to let us know your requirement — and we will deliver a web, mobile, or software solution worthy of outstanding business success. Malik Solution Private Limited is the Market Leader in Managed IT Services and Solutions, IT Support, and Disaster Recovery Services.'],
                'who_extra' => ['More paragraphs (optional — add as many as you want; leave one BLANK LINE between each paragraph)', 'textarea', ''],
            ],
            'Experience Band (30 Years)' => [
                'exp_num'   => ['Big number', 'text', '30'],
                'exp_title' => ['Heading', 'text', 'Years of IT Excellence'],
                'exp_text1' => ['Paragraph 1', 'textarea', 'Malik Solution Private Limited has spent nearly 30 years designing, configuring, and supporting business-critical IT platforms for our customers.'],
                'exp_text2' => ['Paragraph 2', 'textarea', 'We help organizations of all sizes protect their data and reduce the costs of running their IT systems through our IT Support, Managed Services, and Disaster Recovery Services.'],
            ],
            'Mission & Vision' => [
                'mission_text' => ['Mission paragraph', 'textarea', 'By providing these services, our mission is to become the preeminent provider in the market. We are improving the quality of our services consistently — and have become capable of adding value for clients with the help of our hardworking and honest experts. We provide innovation and quality performance to our honored clients.'],
                'vision_text'  => ['Vision paragraph', 'textarea', 'A better future for our clients and customers through the power of technology. We offer world-class IT and disaster recovery services and IT solutions that take businesses to the next level — and we are here to serve you with honor and honesty.'],
            ],
            'Our Approach' => [
                'approach_text' => ['Client approach paragraph', 'textarea', 'As an IT service company, we work separately with every client so that you can find the best technology solutions according to your demands. It does not matter which service you choose — you always get an innovative approach and the most current technology. We have expert teams for each service, and our team members are continuously working on improvement, innovation, and the best solutions to your problems — helping the company maintain a record of success.'],
                'global_text'   => ['Worldwide service paragraph', 'textarea', 'We service customers large and small — commercial and public sector — across a wide range of industries, and we deliver our services worldwide through our unique approach to partnering with clients. Our clients take full advantage of our technology and disaster recovery services and solutions, for a better future powered by technology.'],
            ],
            'Team' => [
                'member1_name'  => ['Member 1 — name', 'text', 'Shahid Malik'],
                'member1_role'  => ['Member 1 — role', 'text', 'Chief Executive Officer'],
                'member1_photo' => ['Member 1 — photo', 'image', 'Shahid.jpeg', '600 × 700 px (portrait)'],
                'member2_name'  => ['Member 2 — name', 'text', 'Muhammad Zahid'],
                'member2_role'  => ['Member 2 — role', 'text', 'Director'],
                'member2_photo' => ['Member 2 — photo', 'image', 'Zahid.png', '600 × 700 px (portrait)'],
            ],
            'Images' => [
                'main_image' => ['Who We Are image', 'image', 'Hero-About.png', '1200 × 700 px (landscape)'],
            ],
        ],
    ],

    'web-development' => [
        'label' => 'Web Development', 'icon' => 'fa-code',
        'sections' => [
            'Page Hero' => [
                'hero_title' => ['Heading', 'text', 'Web Development Services'],
                'hero_lead'  => ['Sub text', 'textarea', 'Interactive, robust, and easy-to-use web solutions that represent your business — built with the latest technologies for the education, health, and business sectors.'],
            ],
            'Intro' => [
                'intro_title' => ['Heading', 'text', 'A Professional, Highly Skilled Web Design & Development Company'],
                'intro_text'  => ['Paragraph', 'textarea', 'Malik Solution provides web design and development services worldwide. A website marks the online presence of any business and opens the way into global markets — we make sure yours is fast, modern, and built to convert visitors into customers.'],
            ],
            'Images' => [
                'intro_image' => ['Intro section image', 'image', 'Website-Development-Company.jpg', '1200 × 800 px (landscape)'],
            ],
        ],
    ],

    'it-services' => [
        'label' => 'IT Services', 'icon' => 'fa-server',
        'sections' => [
            'Page Hero' => [
                'hero_title' => ['Heading', 'text', 'Managed IT Services'],
                'hero_lead'  => ['Sub text', 'textarea', 'Market leader in Managed IT Services and Solutions, IT Support, and Disaster Recovery — we keep your systems running so you can focus on growing your business.'],
            ],
            'Intro' => [
                'intro_title' => ['Heading', 'text', 'Why Businesses Trust Our IT Services'],
                'intro_text'  => ['Paragraph', 'textarea', 'Malik Solution provides an in-house helpdesk with escalation to level-3 consultants, available to all our customers — backed by engineers on staff and free monthly training.'],
            ],
            'Images' => [
                'intro_image'   => ['Key facts image', 'image', 'Hero-IT-Services.jpg', '1200 × 800 px (landscape)'],
                'windows_image' => ['Windows section image', 'image', 'Managed-IT.png', '1200 × 800 px (landscape)'],
            ],
        ],
    ],

    'mobile-app' => [
        'label' => 'Mobile Apps', 'icon' => 'fa-mobile-screen-button',
        'sections' => [
            'Page Hero' => [
                'hero_title' => ['Heading', 'text', 'Mobile App Development Services'],
                'hero_lead'  => ['Sub text', 'textarea', 'Years of experience delivering outstanding Android and iOS applications — innovative solutions that complement your workflow and needs.'],
            ],
            'Intro' => [
                'intro_title' => ['Heading', 'text', 'Experienced in Every Aspect of Mobile Development'],
                'intro_text'  => ['Paragraph', 'textarea', 'Our expert teams come up with innovative solutions for delivering outstanding mobile applications. We focus on the user experience to deliver an app that satisfies the customer — and we always make sure you get the right solution for your needs and budget.'],
            ],
            'Images' => [
                'intro_image' => ['Intro section image', 'image', 'Hero-Mobile-App.png', '1200 × 800 px (landscape)'],
            ],
        ],
    ],

    'hosting' => [
        'label' => 'Hosting', 'icon' => 'fa-cloud',
        'sections' => [
            'Page Hero' => [
                'hero_title' => ['Heading', 'text', 'Reliable, Scalable & Secure Web Hosting'],
                'hero_lead'  => ['Sub text', 'textarea', 'Hosting and maintenance solutions made easy and affordable — with packages to suit your unique needs and budget.'],
            ],
            'Plan 1 — Starter' => [
                'plan1_name'     => ['Name', 'text', 'Starter Plan'],
                'plan1_price'    => ['Price (Rs./yr)', 'text', '1,999'],
                'plan1_features' => ['Features (one per line)', 'textarea', "Host 1 Domain\n1 GB Disk Space\n20 GB Bandwidth\n5 Free Email Accounts\n2 Free Databases\n2 Free FTP Accounts\n2 Free Sub Domains"],
            ],
            'Plan 2 — Economy (highlighted)' => [
                'plan2_name'     => ['Name', 'text', 'Economy Plan'],
                'plan2_price'    => ['Price (Rs./yr)', 'text', '2,999'],
                'plan2_features' => ['Features (one per line)', 'textarea', "Host 2 Domains\n3 GB Disk Space\n40 GB Bandwidth\n10 Free Email Accounts\n5 Free Databases\n5 Free FTP Accounts\n5 Free Sub Domains"],
            ],
            'Plan 3 — Business' => [
                'plan3_name'     => ['Name', 'text', 'Business Plan'],
                'plan3_price'    => ['Price (Rs./yr)', 'text', '3,999'],
                'plan3_features' => ['Features (one per line)', 'textarea', "Host 3 Domains\n6 GB Disk Space\n60 GB Bandwidth\n15 Free Email Accounts\n10 Free Databases\n10 Free FTP Accounts\n10 Free Sub Domains"],
            ],
            'Plan 4 — Deluxe' => [
                'plan4_name'     => ['Name', 'text', 'Deluxe Plan'],
                'plan4_price'    => ['Price (Rs./yr)', 'text', '4,999'],
                'plan4_features' => ['Features (one per line)', 'textarea', "Host 5 Domains\n10 GB Disk Space\n100 GB Bandwidth\n50 Free Email Accounts\n25 Free Databases\n25 Free FTP Accounts\n10 Free Sub Domains"],
            ],
            'Images' => [
                'feature_image' => ['Why Malik Hosting image', 'image', 'Hero-Hosting.png', '1200 × 800 px (landscape)'],
            ],
        ],
    ],

    'demonstration' => [
        'label' => 'Demonstration', 'icon' => 'fa-circle-play',
        'sections' => [
            'Page Hero' => [
                'hero_kicker' => ['Small badge text', 'text', 'Live Product Demonstration'],
                'hero_title'  => ['Heading (text between * * shows in gradient color)', 'textarea', 'Experience Our *Solutions Live*'],
                'hero_lead'   => ['Sub text', 'textarea', 'See our websites, ERP software, mobile apps, hosting solutions and custom software in action.'],
            ],
            'Choose a Demonstration' => [
                'choose_title' => ['Heading', 'text', 'What Would You Like to See?'],
                'choose_lead'  => ['Sub text', 'textarea', "Pick a solution — we'll walk you through a real, working example built by our team."],
                'choose_cards' => ['Cards (one per line: Title | Description)', 'textarea', "Website Development | Corporate sites, portfolios, and landing pages that convert visitors into customers.\nMobile Applications | Android & iOS apps with payments, maps, and push notifications — built to delight.\nERP Software | School, hospital, and business ERPs — attendance, fees, HR, accounts, and reports.\nCloud Hosting | SSD hosting with SSL, daily backups, and 24/7 monitoring — see the panel live.\nCustom Software | POS, inventory, and workflow systems tailored exactly to your business process.\nAI Solutions | Chatbots, automation, and smart analytics that put AI to work for your business."],
            ],
            'Interactive Preview' => [
                'preview_title' => ['Heading', 'text', 'One Platform, Many Solutions'],
                'preview_lead'  => ['Sub text', 'textarea', 'Switch between solution types to preview the kind of system we will demonstrate for you.'],
            ],
            'ERP Modules' => [
                'modules_title' => ['Heading', 'text', 'Every Module Your Institution Needs'],
                'modules_lead'  => ['Sub text', 'textarea', 'Our ERP systems ship with complete, ready-to-use modules — see any of them live in your demo.'],
                'modules_list'  => ['Modules (one name per line)', 'textarea', "Dashboard\nStudents\nAttendance\nFee Collection\nHR\nPayroll\nAccounts\nLibrary\nTransport\nHostel\nExams\nReports"],
            ],
            'Portfolio' => [
                'folio_title' => ['Heading', 'text', 'Built by Malik Solution'],
                'folio_lead'  => ['Sub text', 'textarea', 'A taste of what we can demonstrate — swipe or scroll to explore.'],
                'folio_list'  => ['Projects (one per line: Title | Tech1, Tech2, Tech3)', 'textarea', "Corporate Website | PHP, Bootstrap, MySQL\nSchool Website + ERP | Laravel, MySQL, Bootstrap\nCollege Website | PHP, JavaScript, MySQL\nHospital Website | Laravel, React, MySQL\nE-Commerce Website | WordPress, WooCommerce\nBusiness Website | PHP, Bootstrap, AJAX"],
            ],
            'Mobile Applications' => [
                'mob_title' => ['Heading', 'text', 'Native Feel, Real Business Power'],
                'mob_lead'  => ['Sub text', 'textarea', 'Every mobile demo shows a real, installable app — complete with the integrations your business needs.'],
            ],
            'Hosting Demonstration' => [
                'host_title' => ['Heading', 'text', "See Malik Hosting's Control Panel Live"],
                'host_lead'  => ['Sub text', 'textarea', "In your demo we'll create a live hosting account in front of you — domain, SSL, email, and backups configured in minutes."],
                'host_list'  => ['Features (one per line — splits into 2 columns)', 'textarea', "SSD Hosting\nDaily Backup\nCloud Servers\nFree SSL Certificate\nDomain Management\nAdvanced Security\n24/7 Monitoring\n99.99% Uptime"],
            ],
            'How It Works' => [
                'how_title' => ['Heading', 'text', 'From Demo to Delivery in 5 Steps'],
                'how_steps' => ['Steps (one per line: Title | Description)', 'textarea', "Book Demo | Fill the form below or WhatsApp us — pick a day that suits you.\nRequirement Discussion | We listen first: your business, your goals, your budget.\nLive Demonstration | A real working system, demonstrated live — ask anything.\nQuotation | A clear written quote with price, timeline, and deliverables.\nProject Starts | Approve, and our team gets to work the same week."],
            ],
            'Why Choose Us' => [
                'why_title' => ['Heading', 'text', 'Why Clients Choose Malik Solution'],
                'why_cards' => ['Boxes (one per line: Title | Description)', 'textarea', "Experienced Team | 30+ engineers with expert teams dedicated to each service.\nFast Delivery | Agile process — most demos become live projects within weeks.\nAffordable Pricing | The right solution for your budget, from Rs.1,999/yr hosting up.\nLatest Technologies | React, Laravel, Flutter, AWS, Azure — always current, never outdated.\nSecure Development | Security audits, penetration testing, and safe coding standards.\n24/7 Support | Round-the-clock monitoring and a helpdesk that actually answers."],
            ],
            'Booking Form' => [
                'intro_title' => ['Heading', 'text', 'Book Your Free Demonstration'],
                'intro_text'  => ['Sub text', 'textarea', 'No cost, no obligation — a 30-minute live session on WhatsApp, Zoom, or at our office.'],
            ],
            'Images' => [
                'hosting_image' => ['Hosting section image', 'image', 'Hero-Hosting.png', '1200 × 800 px (landscape)'],
            ],
        ],
    ],

    'projects' => [
        'label' => 'Projects', 'icon' => 'fa-diagram-project',
        'sections' => [
            'Page Hero' => [
                'hero_kicker' => ['Small badge text', 'text', 'Our Portfolio'],
                'hero_title'  => ['Heading (text between * * shows in gradient color)', 'textarea', 'Projects That Deliver *Real Business Results*'],
                'hero_lead'   => ['Sub text', 'textarea', 'Discover our collection of websites, ERP software, mobile applications, hosting solutions and custom software developed for businesses across multiple industries.'],
            ],
            'Featured Heading' => [
                'feat_title' => ['Heading', 'text', "Work We're Proud Of"],
                'feat_lead'  => ['Sub text', 'textarea', 'Three flagship builds — and a full gallery below, filterable by category.'],
            ],
            'All Projects (first 3 = featured)' => [
                'projects_data' => ['Each project = one block (blank line between blocks). Lines: 1) Name  2) categories | Category Label | Year | Status — categories from: web erp mobile hosting custom ai  3) Technologies (comma separated)  4) Short description  5) Features (separate with ; )  6) Industry | Duration | Platform  7) OPTIONAL for featured: Stat chip;Stat chip', 'textarea',
"EduPortal ERP\nerp mobile | ERP Software | 2025 | Live\nPHP, Laravel, MySQL, Bootstrap, Flutter\nComplete campus management platform — admissions, fees, exams, and parent mobile apps in one system.\nOnline Admissions;Fee Collection & Receipts;Attendance & Timetable;Exams & Report Cards;Teacher / Student / Parent Portals;SMS & Email Alerts\nEducation | 4 months | Web + Android + iOS\n1,842 Students;Rs 4.6M Collected\n\nMalik Hosting\nhosting | Hosting | 2024 | Live\nPHP, MySQL, Cloud Servers, REST API\nOur own hosting brand — SSD cloud hosting with automated provisioning, SSL, and daily backups.\nAutomated Account Provisioning;One-Click SSL;Daily Off-site Backups;Email & FTP Management;Uptime Monitoring;Billing & Invoicing\nTechnology | Ongoing | Web\n99.99% Uptime;Daily Backups\n\nOrnate College\nweb | Web Development | 2025 | Live\nPHP, Bootstrap, MySQL, JavaScript\nModern college website with admissions, faculty profiles, notice board, and an integrated student portal.\nOnline Admission Forms;Faculty Directory;Events & Notices;Downloads Center;Student Portal;Custom Admin Panel\nEducation | 6 weeks | Web\nOnline Admissions;Student Portal\n\nSchool Management System\nerp | ERP Software | 2024 | Live\nPHP, MySQL, Bootstrap\nAttendance, fees, payroll, and report cards for a growing school network.\nMulti-Branch Support;Biometric Attendance;Fee Vouchers;Staff Payroll;Report Cards;Head-Office Reports\nEducation | 3 months | Web\n\nHospital Management System\nerp custom | ERP Software | 2024 | Live\nLaravel, MySQL, Bootstrap\nOPD, appointments, pharmacy, and billing — one system for the whole hospital.\nOPD & Appointments;Lab Reports;Pharmacy Stock;Bed Management;Billing & Insurance;Doctor Dashboards\nHealthcare | 5 months | Web\n\nRestaurant POS\ncustom mobile | Custom Software | 2025 | Live\nPHP, MySQL, Flutter, REST API\nTouch-friendly POS with kitchen display, rider app, and daily sales reports.\nTouch Ordering;Kitchen Display;Rider Mobile App;Deals & Discounts;Shift Closing;Owner Sales Reports\nRestaurants | 2 months | Web + Android\n\nInventory Software\ncustom ai | Custom Software | 2025 | Live\nPHP, MySQL, JavaScript, REST API\nStock, purchases, and smart low-stock forecasting for a distribution business.\nBarcode Support;Purchase & Sales Invoices;Supplier Ledgers;Low-Stock Alerts;AI Reorder Forecasting;Profit Reports\nRetail & Distribution | 10 weeks | Web\n\nBusiness Website\nweb | Web Development | 2024 | Live\nPHP, Bootstrap, MySQL\nCorporate profile website with services, portfolio, and lead-generating contact forms.\nService Pages;Project Gallery;Testimonials;Enquiry Forms;SEO Setup;Admin Inbox\nBusiness Services | 3 weeks | Web\n\nReal Estate Website\nweb | Web Development | 2025 | Live\nPHP, MySQL, JavaScript, Bootstrap\nProperty listings with search filters, image galleries, and agent enquiry system.\nProperty Listings;Search & Filters;Photo Galleries;Featured Projects;Agent Profiles;WhatsApp Enquiry\nReal Estate | 6 weeks | Web\n\nE-Commerce Website\nweb mobile | Web Development | 2025 | Live\nWordPress, WooCommerce, Flutter\nOnline store with cart, payment gateway, order tracking, and a customer mobile app.\nProduct Variations;Cart & Checkout;Card + COD Payments;Order Tracking;Discount Coupons;Customer Mobile App\nRetail | 2 months | Web + Android\n\nNGO Website\nweb | Web Development | 2024 | Live\nPHP, Bootstrap, MySQL\nCause-driven website with donation campaigns, volunteer forms, and impact reports.\nCampaign Pages;Online Donations;Volunteer Registration;Photo Stories;Impact Reports;Newsletter Signup\nNon-Profit | 4 weeks | Web\n\nCorporate Website\nweb | Web Development | 2025 | Live\nLaravel, MySQL, Bootstrap, REST API\nPremium multi-page corporate site with careers section and multilingual support.\nLeadership Profiles;Careers & Applications;News Room;Investor Pages;English/Urdu Support;Custom CMS\nCorporate | 2 months | Web"],
            ],
            'Gallery Heading' => [
                'gal_title' => ['Heading', 'text', 'Explore the Full Portfolio'],
                'gal_lead'  => ['Sub text', 'textarea', 'Use the category filters above — every card opens a detailed case view.'],
            ],
            'Technologies' => [
                'tech_title' => ['Heading', 'text', 'Modern Stack, Proven Results'],
                'tech_list'  => ['Technologies (one per line)', 'textarea', "PHP\nLaravel\nBootstrap\nMySQL\nFlutter\nReact\nNode.js\nJavaScript\nHTML5\nCSS3\nTailwind CSS\nREST API"],
            ],
            'Development Process' => [
                'proc_title' => ['Heading', 'text', 'How Every Project Gets Built'],
                'proc_steps' => ['Steps (one per line: Title | Description)', 'textarea', "Requirement Analysis | We document your goals, users, and scope before writing a line of code.\nUI Design | Clean, modern interfaces designed for your brand and your users.\nDevelopment | Agile sprints with weekly progress you can actually see.\nTesting | Functional, security, and device testing before anything ships.\nDeployment | Launch on our hosting or yours — SSL, backups, and monitoring included.\nSupport | Training, maintenance, and 24/7 support after go-live."],
            ],
            'Statistics' => [
                'stats_list' => ['Counters (one per line: Number | Label — number may end with + or %)', 'textarea', "500+ | Projects Completed\n250+ | Happy Clients\n10+ | Years Experience\n99% | Client Satisfaction\n20+ | Industries Served"],
            ],
            'Industries' => [
                'ind_title' => ['Heading', 'text', 'Solutions for Every Sector'],
                'ind_list'  => ['Industries (one per line)', 'textarea', "Education\nHealthcare\nBusiness\nRetail\nRestaurants\nConstruction\nNGOs\nReal Estate\nManufacturing\nLogistics"],
            ],
            'Testimonials' => [
                'testi_title' => ['Heading', 'text', 'What Our Clients Say'],
                'testi_list'  => ['Reviews (one per line: Name | Role — Company | Review text)', 'textarea', "Prof. Imran Qureshi | Principal — Ornate College | Malik Solution built our complete college website and ERP. Admissions that took days on paper now happen online in minutes, and the fee reports alone have saved our accounts office hours every week.\nSana Malik | Owner — SM Boutique | Our e-commerce store was delivered in under two months, with the mobile app included. Orders come in around the clock and the team still answers our WhatsApp within minutes whenever we need a change.\nKashif Ali | IT Manager — Karachi Traders | They migrated our website and email to Malik Hosting and the random downtime we lived with for years simply disappeared. Daily backups and monitoring mean I finally sleep well."],
            ],
            'Bottom Call-To-Action' => [
                'cta_title' => ['Heading', 'text', "Let's Build Your Next Digital Solution"],
                'cta_text'  => ['Sub text', 'textarea', 'Whether you need a website, ERP system, mobile application or custom software, Malik Solution is ready to help.'],
            ],
        ],
    ],

    'contact' => [
        'label' => 'Contact', 'icon' => 'fa-envelope',
        'sections' => [
            'Page Hero' => [
                'hero_title' => ['Heading', 'text', 'Contact Malik Solution'],
                'hero_lead'  => ['Sub text', 'textarea', 'Tell us about your project — web, mobile, software, IT services, or hosting — and we will get back to you shortly.'],
            ],
            'Details' => [
                'working_hours' => ['Working hours line', 'text', 'Monday to Saturday, 9:00 AM – 6:00 PM'],
            ],
        ],
    ],
];
