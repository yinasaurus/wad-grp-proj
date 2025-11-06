-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql113.infinityfree.com
-- Generation Time: Nov 06, 2025 at 02:55 PM
-- Server version: 11.4.7-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_40329348_greenbiz`
--

-- --------------------------------------------------------

--
-- Table structure for table `businesses`
--

CREATE TABLE `businesses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `lat` decimal(9,6) DEFAULT NULL,
  `lng` decimal(9,6) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `longDescription` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `sustainability_score` int(11) DEFAULT 0,
  `verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `businesses`
--

INSERT INTO `businesses` (`id`, `user_id`, `name`, `category`, `address`, `lat`, `lng`, `description`, `longDescription`, `phone`, `email`, `website`, `location`, `sustainability_score`, `verified`, `created_at`) VALUES
(1, NULL, 'EcoTech Solutions', 'Technology', '1 Marina Boulevard, Singapore 018989', '1.282100', '103.854500', 'Sustainable IT solutions and green technology provider', 'We specialize in helping businesses transition to eco-friendly technology infrastructure. Our services include energy-efficient data center solutions, carbon-neutral cloud computing, e-waste recycling programs, and sustainable IT consulting. Since 2015, we\'ve helped over 500 companies reduce their carbon footprint while improving operational efficiency.', '+65 6234 5678', 'contact@ecotech.sg', 'https://www.ecotech.sg', NULL, 92, 1, '2025-11-06 03:56:25'),
(2, NULL, 'Green Harvest Cafe', 'Food and Beverage', '100 Orchard Road, Singapore 238840', '1.304800', '103.831800', 'Farm-to-table organic restaurant with sustainable practices', 'Our cafe serves 100% organic, locally-sourced ingredients with a zero food waste policy. We compost all organic waste, use only biodegradable packaging, and support local farmers. Our menu changes seasonally to reduce carbon footprint and ensure the freshest ingredients.', '+65 6234 5679', 'hello@greenharvest.sg', 'https://www.greenharvest.sg', NULL, 88, 1, '2025-11-06 03:56:25'),
(3, NULL, 'Solar Power Plus', 'Energy', '50 Jurong Gateway Road, Singapore 608549', '1.333900', '103.743600', 'Renewable energy installations and solar panel solutions', 'Leading provider of solar energy solutions for residential and commercial properties. We have installed over 500 solar systems across Singapore, generating over 50MW of clean energy annually. Our team of certified engineers ensures optimal system design and installation.', '+65 6234 5680', 'info@solarpowerplus.sg', 'https://www.solarpowerplus.sg', NULL, 95, 1, '2025-11-06 03:56:25'),
(4, NULL, 'EcoMart Retail', 'Retail', '10 Tampines Central, Singapore 529536', '1.353800', '103.944600', 'Zero-waste retail store offering sustainable products', 'Singapore\'s first zero-waste retail store. We offer package-free shopping with bulk bins, refill stations, and eco-friendly alternatives to everyday products. Our mission is to make sustainable living accessible and affordable for everyone.', '+65 6234 5681', 'shop@ecomart.sg', 'https://www.ecomart.sg', NULL, 85, 1, '2025-11-06 03:56:25'),
(5, NULL, 'GreenBuild Manufacturing', 'Manufacturing', '15 Woodlands Industrial Park, Singapore 738322', '1.450100', '103.794900', 'Sustainable manufacturing with eco-friendly materials', 'We manufacture construction materials using recycled and sustainable resources. Our factory runs on 100% renewable energy and implements circular economy principles. We\'re committed to revolutionizing the construction industry with sustainable alternatives.', '+65 6234 5682', 'sales@greenbuild.sg', 'https://www.greenbuild.sg', NULL, 90, 1, '2025-11-06 03:56:25'),
(6, NULL, 'Eco Consulting Services', 'Services', '20 Cecil Street, Singapore 049705', '1.282500', '103.849900', 'Environmental consulting and sustainability advisory services', 'We help businesses achieve their sustainability goals through expert consulting, audits, and strategic planning. Our team of environmental specialists has helped over 200 companies reduce their environmental impact while improving profitability.', '+65 6234 5683', 'consult@ecoconsulting.sg', 'https://www.ecoconsulting.sg', NULL, 87, 1, '2025-11-06 03:56:25'),
(7, NULL, 'SmartEco Systems', 'Technology', '10 Anson Road, Singapore 079903', '1.276500', '103.845700', 'Smart automation and IoT for energy-efficient buildings', 'We create intelligent building solutions using IoT sensors and analytics to cut energy waste by up to 40%. Our platform powers offices, schools, and smart homes across Singapore.', '+65 6123 7001', 'info@smarteco.sg', 'https://www.smarteco.sg', NULL, 91, 1, '2025-11-06 03:56:25'),
(8, NULL, 'GreenCrust Pizza', 'Food and Beverage', '1 HarbourFront Walk, Singapore 098585', '1.264900', '103.822200', 'Eco-friendly pizza restaurant using local ingredients', 'Our pizzas are made with locally-sourced organic produce, zero food waste kitchens, and compostable packaging. We run on 100% solar energy from rooftop panels.', '+65 6123 7002', 'hello@greencrust.sg', 'https://www.greencrust.sg', NULL, 87, 1, '2025-11-06 03:56:25'),
(9, NULL, 'PureHydro Energy', 'Energy', '21 Jurong Island Highway, Singapore 627847', '1.291000', '103.720000', 'Hydrogen and renewable energy innovation company', 'We produce green hydrogen and help industries transition from fossil fuels. Our systems are used by factories and research centers across Asia.', '+65 6123 7003', 'contact@purehydro.sg', 'https://www.purehydro.sg', NULL, 94, 1, '2025-11-06 03:56:25'),
(10, NULL, 'ReLeaf Organics', 'Retail', '2 Handy Road, Singapore 229233', '1.297500', '103.845000', 'Eco retail brand specializing in sustainable skincare and wellness products', 'We offer certified organic skincare, refill stations, and biodegradable packaging to reduce single-use plastics.', '+65 6123 7004', 'sales@releaf.sg', 'https://www.releaf.sg', NULL, 86, 1, '2025-11-06 03:56:25'),
(11, NULL, 'BioMatter Manufacturing', 'Manufacturing', '45 Tuas View Close, Singapore 637441', '1.307700', '103.635800', 'Biodegradable material manufacturer', 'We create next-generation bio-based plastics that decompose naturally within 90 days, replacing petroleum plastics in packaging.', '+65 6123 7005', 'info@biomatter.sg', 'https://www.biomatter.sg', NULL, 92, 1, '2025-11-06 03:56:25'),
(12, NULL, 'EcoFleet Services', 'Services', '18 Ubi Avenue 3, Singapore 408868', '1.333300', '103.894000', 'Carbon-neutral logistics and delivery service', 'We operate Singapore’s first all-electric delivery fleet, cutting 5,000 tons of CO₂ emissions annually while ensuring on-time deliveries.', '+65 6123 7006', 'support@ecofleet.sg', 'https://www.ecofleet.sg', NULL, 90, 1, '2025-11-06 03:56:25'),
(13, NULL, 'CleanPower Technologies', 'Technology', '60 Robinson Road, Singapore 068892', '1.279500', '103.849000', 'Renewable energy analytics software company', 'Our AI-driven dashboards track energy use and carbon output, helping corporations achieve sustainability targets efficiently.', '+65 6123 7007', 'contact@cleanpower.sg', 'https://www.cleanpower.sg', NULL, 88, 1, '2025-11-06 03:56:25'),
(14, NULL, 'ZeroWaste Bakery', 'Food and Beverage', '391 Orchard Road, Singapore 238872', '1.303500', '103.832000', 'Sustainable artisan bakery minimizing food waste', 'All baked goods are produced daily with surplus redistributed via charity partners. 100% compostable packaging and solar ovens.', '+65 6123 7008', 'hello@zerowastebakery.sg', 'https://www.zerowastebakery.sg', NULL, 84, 1, '2025-11-06 03:56:25'),
(15, NULL, 'Solara Energy Systems', 'Energy', '32 Changi South Avenue 2, Singapore 486478', '1.334100', '103.962300', 'Solar panel installation and consulting company', 'We have installed 1,200 rooftop solar systems across Singapore, producing 30 MW of renewable electricity yearly.', '+65 6123 7009', 'sales@solara.sg', 'https://www.solara.sg', NULL, 93, 1, '2025-11-06 03:56:25'),
(16, NULL, 'EcoHaven Market', 'Retail', '180 Kitchener Road, Singapore 208539', '1.310000', '103.856000', 'Zero-waste grocery selling bulk eco products', 'We promote sustainable shopping by offering refill stations and reusable container programs, eliminating over 100,000 plastic bags yearly.', '+65 6123 7010', 'shop@ecohaven.sg', 'https://www.ecohaven.sg', NULL, 85, 1, '2025-11-06 03:56:25'),
(17, NULL, 'BlueWave Manufacturing', 'Manufacturing', '33 Tuas South Street 7, Singapore 637340', '1.305100', '103.637400', 'Water-efficient component manufacturing plant', 'Our factory recycles 90% of wastewater through closed-loop filtration, saving 1 million liters per month.', '+65 6123 7011', 'info@bluewave.sg', 'https://www.bluewave.sg', NULL, 89, 1, '2025-11-06 03:56:25'),
(18, NULL, 'SustainHub Consultancy', 'Services', '10 Collyer Quay, Singapore 049315', '1.283000', '103.851100', 'Corporate sustainability consulting firm', 'We guide organizations in ESG reporting, carbon accounting, and strategy to achieve carbon neutrality.', '+65 6123 7012', 'consult@sustainhub.sg', 'https://www.sustainhub.sg', NULL, 87, 1, '2025-11-06 03:56:25'),
(19, NULL, 'GreenFuel Energy', 'Energy', '5 Benoi Place, Singapore 629926', '1.322500', '103.679400', 'Biofuel production company', 'We convert food waste into clean biofuels for transportation and industrial use, supporting Singapore’s circular economy.', '+65 6123 7013', 'info@greenfuel.sg', 'https://www.greenfuel.sg', NULL, 91, 1, '2025-11-06 03:56:25'),
(20, NULL, 'FreshLeaf Cafe', 'Food and Beverage', '9 Dempsey Road, Singapore 249672', '1.305700', '103.815500', 'Farm-to-table vegetarian restaurant', 'We serve plant-based meals sourced directly from local farms, reducing supply-chain carbon emissions by 80%.', '+65 6123 7014', 'hello@freshleaf.sg', 'https://www.freshleaf.sg', NULL, 88, 1, '2025-11-06 03:56:25'),
(21, NULL, 'UrbanTech Solutions', 'Technology', '55 Newton Road, Singapore 307987', '1.317100', '103.842400', 'Smart city infrastructure and automation', 'We build urban sensor networks for smart traffic, air quality, and waste monitoring to improve urban sustainability.', '+65 6123 7015', 'support@urbantech.sg', 'https://www.urbantech.sg', NULL, 89, 1, '2025-11-06 03:56:25'),
(22, NULL, 'EcoGlow Lighting', 'Manufacturing', '20 Senoko Loop, Singapore 758170', '1.460200', '103.805900', 'Energy-efficient LED lighting manufacturer', 'We design LED systems that reduce energy use by 70% while offering recyclable aluminum housings.', '+65 6123 7016', 'sales@ecoglow.sg', 'https://www.ecoglow.sg', NULL, 90, 1, '2025-11-06 03:56:25'),
(23, NULL, 'PureCycle Laundry', 'Services', '50 Bukit Batok Street 23, Singapore 659578', '1.349000', '103.750900', 'Eco laundry using waterless technology', 'Our closed-loop cleaning process eliminates detergent waste and saves 80% of traditional water usage.', '+65 6123 7017', 'contact@purecycle.sg', 'https://www.purecycle.sg', NULL, 86, 1, '2025-11-06 03:56:25'),
(24, NULL, 'ReGrow Foods', 'Food and Beverage', '200 Upper Thomson Road, Singapore 574424', '1.349800', '103.832500', 'Hydroponic urban farming and cafe', 'Our hydroponic produce reduces transport emissions and provides ultra-fresh salads and smoothies daily.', '+65 6123 7018', 'farm@regrow.sg', 'https://www.regrow.sg', NULL, 92, 1, '2025-11-06 03:56:25'),
(25, NULL, 'NextGen Renewables', 'Energy', '38 Gul Road, Singapore 629681', '1.316700', '103.679500', 'Wind and solar hybrid system installer', 'We design hybrid microgrids for remote sites and industrial estates to ensure 24/7 renewable energy availability.', '+65 6123 7019', 'info@nextgen.sg', 'https://www.nextgen.sg', NULL, 93, 1, '2025-11-06 03:56:25'),
(26, NULL, 'GreenCart Retail', 'Retail', '8 Boon Tat Street, Singapore 069614', '1.280500', '103.849400', 'Online marketplace for sustainable goods', 'We feature over 2,000 eco-friendly products verified for sustainable sourcing and fair-trade practices.', '+65 6123 7020', 'support@greencart.sg', 'https://www.greencart.sg', NULL, 84, 1, '2025-11-06 03:56:25'),
(27, NULL, 'EcoMach Manufacturing', 'Manufacturing', '29 Pioneer Crescent, Singapore 628438', '1.316200', '103.699900', 'Manufacturer of energy-efficient machinery', 'We produce industrial machines that consume 40% less energy and are built from recycled steel.', '+65 6123 7021', 'contact@ecomach.sg', 'https://www.ecomach.sg', NULL, 90, 1, '2025-11-06 03:56:25'),
(28, NULL, 'GreenLink IT', 'Technology', '3 Temasek Avenue, Singapore 039190', '1.291000', '103.857400', 'IT solutions company specializing in sustainability data', 'We develop APIs that integrate carbon tracking and ESG compliance into enterprise systems.', '+65 6123 7022', 'info@greenlink.sg', 'https://www.greenlink.sg', NULL, 88, 1, '2025-11-06 03:56:25'),
(29, NULL, 'ZeroCarbon Services', 'Services', '1 North Bridge Road, Singapore 179094', '1.292300', '103.849900', 'Net-zero transition advisory firm', 'We assist companies in carbon auditing, green certification, and achieving Singapore Green Plan 2030 goals.', '+65 6123 7023', 'hello@zerocarbon.sg', 'https://www.zerocarbon.sg', NULL, 91, 1, '2025-11-06 03:56:25'),
(30, NULL, 'SolarGrid Energy', 'Energy', '12 Tuas Avenue 9, Singapore 639174', '1.318800', '103.641100', 'Solar battery and storage provider', 'We design and install advanced solar energy storage systems that ensure grid stability and renewable backup.', '+65 6123 7024', 'sales@solargrid.sg', 'https://www.solargrid.sg', NULL, 94, 1, '2025-11-06 03:56:25'),
(31, NULL, 'GreenCafe Collective', 'Food and Beverage', '6 Eu Tong Sen Street, Singapore 059817', '1.288300', '103.846200', 'Sustainable coffee chain using fair-trade beans', 'We serve ethically sourced coffee, reuse coffee grounds for compost, and use paper-free digital menus.', '+65 6123 7025', 'contact@greencafe.sg', 'https://www.greencafe.sg', NULL, 85, 1, '2025-11-06 03:56:25'),
(32, NULL, 'EcoWare Manufacturing', 'Manufacturing', '39 Jalan Buroh, Singapore 619494', '1.307300', '103.720900', 'Biodegradable packaging manufacturer', 'Our food-grade packaging decomposes naturally within 90 days, replacing millions of single-use plastics yearly.', '+65 6123 7026', 'info@ecoware.sg', 'https://www.ecoware.sg', NULL, 93, 1, '2025-11-06 03:56:25'),
(33, NULL, 'UrbanServe Solutions', 'Services', '20 Maxwell Road, Singapore 069113', '1.278700', '103.845600', 'Facilities management and eco maintenance company', 'We provide cleaning, waste management, and green maintenance services for commercial buildings.', '+65 6123 7027', 'support@urbanserve.sg', 'https://www.urbanserve.sg', NULL, 87, 1, '2025-11-06 03:56:25'),
(34, NULL, 'PureEnergy Tech', 'Technology', '50 Raffles Place, Singapore 048623', '1.283000', '103.851500', 'Clean energy monitoring and analytics company', 'We develop smart grid monitoring systems that optimize renewable power distribution and energy savings.', '+65 6123 7028', 'sales@pureenergy.sg', 'https://www.pureenergy.sg', NULL, 90, 1, '2025-11-06 03:56:25'),
(35, NULL, 'LeafLife Market', 'Retail', '3 Gateway Drive, Singapore 608532', '1.333900', '103.742300', 'Retail store promoting green lifestyle products', 'Our curated selection of eco goods supports sustainable habits like composting, recycling, and clean living.', '+65 6123 7029', 'shop@leaflife.sg', 'https://www.leaflife.sg', NULL, 86, 1, '2025-11-06 03:56:25'),
(36, NULL, 'GreenWorks Services', 'Services', '22 Sin Ming Lane, Singapore 573969', '1.354700', '103.837100', 'Sustainable renovation and green facility contractor', 'We retrofit buildings with LED lighting, efficient air systems, and sustainable materials to meet BCA Green Mark standards.', '+65 6123 7030', 'contact@greenworks.sg', 'https://www.greenworks.sg', NULL, 89, 1, '2025-11-06 03:56:25'),
(37, 12, 'business1', 'Retail', '', NULL, NULL, 'Green business', NULL, '', 'business1@gmail.com', NULL, '', 0, 0, '2025-11-06 19:55:09');

-- --------------------------------------------------------

--
-- Table structure for table `business_locations`
--

CREATE TABLE `business_locations` (
  `location_id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `location_name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `lat` decimal(9,6) DEFAULT NULL,
  `lng` decimal(9,6) DEFAULT NULL,
  `operating_hours` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `business_locations`
--

INSERT INTO `business_locations` (`location_id`, `business_id`, `location_name`, `address`, `lat`, `lng`, `operating_hours`, `created_at`) VALUES
(1, 1, 'Marina Bay Office', '1 Marina Boulevard, #12-01, Singapore 018989', '1.282191', '103.852514', 'Mon-Fri: 9:00 AM - 6:00 PM', '2025-11-06 03:56:26'),
(2, 1, 'Jurong Facility', '50 Jurong Gateway Road, #05-01, Singapore 608549', '1.333011', '103.743310', 'Mon-Fri: 8:00 AM - 5:00 PM', '2025-11-06 03:56:26'),
(3, 2, 'Orchard Outlet', '100 Orchard Road, #01-05, Singapore 238840', '1.300871', '103.842238', 'Daily: 8:00 AM - 9:00 PM', '2025-11-06 03:56:26'),
(4, 3, 'Jurong Headquarters', '50 Jurong Gateway Road, #10-15, Singapore 608549', '1.333021', '103.743706', 'Mon-Fri: 9:00 AM - 6:00 PM', '2025-11-06 03:56:26'),
(5, 3, 'East Branch', '10 Tampines Central, #03-20, Singapore 529536', '1.354201', '103.945022', 'Mon-Sat: 9:00 AM - 5:00 PM', '2025-11-06 03:56:26'),
(6, 3, 'North Showroom', '15 Woodlands Avenue, #01-10, Singapore 738322', '1.448792', '103.809045', 'Tue-Sat: 10:00 AM - 6:00 PM', '2025-11-06 03:56:26'),
(7, 4, 'Tampines Outlet', '10 Tampines Central, #02-15, Singapore 529536', '1.354201', '103.945023', 'Daily: 10:00 AM - 9:00 PM', '2025-11-06 03:56:26'),
(8, 4, 'Orchard Branch', '100 Orchard Road, #B1-20, Singapore 238840', '1.300821', '103.842237', 'Daily: 11:00 AM - 8:00 PM', '2025-11-06 03:56:26'),
(9, 5, 'Woodlands Factory', '15 Woodlands Industrial Park, Singapore 738322', '1.453861', '103.795706', 'Mon-Fri: 8:00 AM - 5:00 PM', '2025-11-06 03:56:26'),
(10, 6, 'Cecil Street Office', '20 Cecil Street, #15-01, Singapore 049705', '1.282985', '103.850522', 'Mon-Fri: 9:00 AM - 6:00 PM', '2025-11-06 03:56:26'),
(11, 7, 'Downtown Office', '10 Anson Road, #15-01, Singapore 079903', '1.276500', '103.845700', 'Mon-Fri: 9:00 AM - 6:00 PM', '2025-11-06 03:56:26'),
(12, 8, 'HarbourFront Outlet', '1 HarbourFront Walk, #02-10, Singapore 098585', '1.264900', '103.822200', 'Daily: 10:00 AM - 10:00 PM', '2025-11-06 03:56:26'),
(13, 9, 'Jurong Island Plant', '21 Jurong Island Highway, Singapore 627847', '1.291000', '103.720000', 'Mon-Fri: 8:00 AM - 5:00 PM', '2025-11-06 03:56:26'),
(14, 10, 'Dhoby Ghaut Store', '2 Handy Road, #01-03, Singapore 229233', '1.297500', '103.845000', 'Daily: 11:00 AM - 9:00 PM', '2025-11-06 03:56:26'),
(15, 11, 'Tuas Factory', '45 Tuas View Close, Singapore 637441', '1.307700', '103.635800', 'Mon-Fri: 8:00 AM - 5:00 PM', '2025-11-06 03:56:26'),
(16, 12, 'Ubi HQ', '18 Ubi Avenue 3, #04-03, Singapore 408868', '1.333300', '103.894000', 'Mon-Fri: 9:00 AM - 6:00 PM', '2025-11-06 03:56:26'),
(17, 13, 'Robinson Office', '60 Robinson Road, #12-02, Singapore 068892', '1.279500', '103.849000', 'Mon-Fri: 9:00 AM - 6:00 PM', '2025-11-06 03:56:26'),
(18, 14, 'Orchard Branch', '391 Orchard Road, #B1-11, Singapore 238872', '1.303500', '103.832000', 'Daily: 8:00 AM - 8:00 PM', '2025-11-06 03:56:26'),
(19, 15, 'Changi Workshop', '32 Changi South Avenue 2, Singapore 486478', '1.334100', '103.962300', 'Mon-Sat: 8:30 AM - 6:00 PM', '2025-11-06 03:56:26'),
(20, 16, 'City Square Mall', '180 Kitchener Road, #02-15, Singapore 208539', '1.310000', '103.856000', 'Daily: 10:00 AM - 9:00 PM', '2025-11-06 03:56:26'),
(21, 17, 'Tuas South Plant', '33 Tuas South Street 7, Singapore 637340', '1.305100', '103.637400', 'Mon-Fri: 8:00 AM - 5:30 PM', '2025-11-06 03:56:26'),
(22, 18, 'Collyer Office', '10 Collyer Quay, #29-01, Singapore 049315', '1.283000', '103.851100', 'Mon-Fri: 9:00 AM - 6:00 PM', '2025-11-06 03:56:26'),
(23, 19, 'Benoi Facility', '5 Benoi Place, Singapore 629926', '1.322500', '103.679400', 'Mon-Fri: 8:00 AM - 5:00 PM', '2025-11-06 03:56:26'),
(24, 20, 'Dempsey Hill Outlet', '9 Dempsey Road, Singapore 249672', '1.305700', '103.815500', 'Daily: 8:00 AM - 9:00 PM', '2025-11-06 03:56:26'),
(25, 21, 'Newton Office', '55 Newton Road, #06-03, Singapore 307987', '1.317100', '103.842400', 'Mon-Fri: 9:00 AM - 6:00 PM', '2025-11-06 03:56:26'),
(26, 22, 'Senoko Factory', '20 Senoko Loop, Singapore 758170', '1.460200', '103.805900', 'Mon-Fri: 8:00 AM - 5:30 PM', '2025-11-06 03:56:26'),
(27, 23, 'Bukit Batok Outlet', '50 Bukit Batok Street 23, #01-03, Singapore 659578', '1.349000', '103.750900', 'Daily: 9:00 AM - 8:00 PM', '2025-11-06 03:56:26'),
(28, 24, 'Upper Thomson Cafe', '200 Upper Thomson Road, Singapore 574424', '1.349800', '103.832500', 'Daily: 9:00 AM - 9:00 PM', '2025-11-06 03:56:26'),
(29, 25, 'Gul Industrial Site', '38 Gul Road, Singapore 629681', '1.316700', '103.679500', 'Mon-Fri: 8:00 AM - 5:30 PM', '2025-11-06 03:56:26'),
(30, 26, 'Telok Ayer Store', '8 Boon Tat Street, #01-02, Singapore 069614', '1.280500', '103.849400', 'Daily: 10:00 AM - 8:00 PM', '2025-11-06 03:56:26'),
(31, 27, 'Pioneer Plant', '29 Pioneer Crescent, Singapore 628438', '1.316200', '103.699900', 'Mon-Fri: 8:00 AM - 5:30 PM', '2025-11-06 03:56:26'),
(32, 28, 'Suntec Office', '3 Temasek Avenue, #14-02, Singapore 039190', '1.291000', '103.857400', 'Mon-Fri: 9:00 AM - 6:00 PM', '2025-11-06 03:56:26'),
(33, 29, 'North Bridge HQ', '1 North Bridge Road, #11-05, Singapore 179094', '1.292300', '103.849900', 'Mon-Fri: 9:00 AM - 6:00 PM', '2025-11-06 03:56:26'),
(34, 30, 'Tuas Battery Lab', '12 Tuas Avenue 9, Singapore 639174', '1.318800', '103.641100', 'Mon-Fri: 8:00 AM - 5:30 PM', '2025-11-06 03:56:26'),
(35, 31, 'Clarke Quay Cafe', '6 Eu Tong Sen Street, #01-07, Singapore 059817', '1.288300', '103.846200', 'Daily: 8:00 AM - 9:00 PM', '2025-11-06 03:56:26'),
(36, 32, 'Jalan Buroh Factory', '39 Jalan Buroh, Singapore 619494', '1.307300', '103.720900', 'Mon-Fri: 8:00 AM - 5:00 PM', '2025-11-06 03:56:26'),
(37, 33, 'Maxwell HQ', '20 Maxwell Road, #05-01, Singapore 069113', '1.278700', '103.845600', 'Mon-Fri: 9:00 AM - 6:00 PM', '2025-11-06 03:56:26'),
(38, 34, 'Raffles Place Office', '50 Raffles Place, #19-01, Singapore 048623', '1.283000', '103.851500', 'Mon-Fri: 9:00 AM - 6:00 PM', '2025-11-06 03:56:26'),
(39, 35, 'JEM Mall Branch', '3 Gateway Drive, #03-12, Singapore 608532', '1.333900', '103.742300', 'Daily: 10:00 AM - 9:00 PM', '2025-11-06 03:56:26'),
(40, 36, 'Sin Ming Workshop', '22 Sin Ming Lane, #05-68, Singapore 573969', '1.354700', '103.837100', 'Mon-Sat: 9:00 AM - 6:00 PM', '2025-11-06 03:56:26');

-- --------------------------------------------------------

--
-- Table structure for table `business_practices`
--

CREATE TABLE `business_practices` (
  `practice_id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `practice_title` varchar(255) NOT NULL,
  `practice_description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `business_practices`
--

INSERT INTO `business_practices` (`practice_id`, `business_id`, `practice_title`, `practice_description`, `created_at`) VALUES
(1, 1, '100% Renewable Energy', 'All our operations run on solar and wind power, with zero reliance on fossil fuels.', '2025-11-06 03:56:26'),
(2, 1, 'E-Waste Recycling Program', 'We recycle and refurbish electronic equipment, preventing 50 tons of e-waste annually.', '2025-11-06 03:56:26'),
(3, 1, 'Carbon Offset Initiatives', 'We offset 150% of our carbon emissions through verified reforestation projects.', '2025-11-06 03:56:26'),
(4, 1, 'Green Supply Chain', 'All our suppliers meet strict environmental standards and sustainability criteria.', '2025-11-06 03:56:26'),
(5, 2, '100% Organic Ingredients', 'All ingredients sourced from certified organic local farms.', '2025-11-06 03:56:26'),
(6, 2, 'Zero Food Waste', 'Composting program and food donation to eliminate waste.', '2025-11-06 03:56:26'),
(7, 2, 'Biodegradable Packaging', 'All takeaway containers are fully compostable.', '2025-11-06 03:56:26'),
(8, 2, 'Water Conservation', 'Rainwater harvesting and grey water recycling systems.', '2025-11-06 03:56:26'),
(9, 3, 'Clean Energy Generation', 'Installing solar systems that prevent 25,000 tons of CO2 annually.', '2025-11-06 03:56:26'),
(10, 3, 'Recycling Program', '100% recycling of old solar panels and equipment.', '2025-11-06 03:56:26'),
(11, 3, 'Energy Storage Solutions', 'Battery systems to maximize renewable energy usage.', '2025-11-06 03:56:26'),
(12, 3, 'Education Initiatives', 'Free workshops on renewable energy for communities.', '2025-11-06 03:56:26'),
(13, 4, 'Package-Free Shopping', 'Customers bring their own containers to reduce packaging waste.', '2025-11-06 03:56:26'),
(14, 4, 'Plastic-Free Store', 'Zero single-use plastics throughout the entire store.', '2025-11-06 03:56:26'),
(15, 4, 'Local Sourcing', '80% of products sourced from local sustainable suppliers.', '2025-11-06 03:56:26'),
(16, 4, 'Education Programs', 'Weekly workshops on sustainable living practices.', '2025-11-06 03:56:26'),
(17, 5, 'Recycled Materials', '90% of our materials are recycled or sustainably sourced.', '2025-11-06 03:56:26'),
(18, 5, 'Zero Emissions Factory', 'Carbon-neutral production powered by renewable energy.', '2025-11-06 03:56:26'),
(19, 5, 'Circular Economy', 'Take-back program for end-of-life products.', '2025-11-06 03:56:26'),
(20, 5, 'Water Recycling', '95% of water used in production is recycled.', '2025-11-06 03:56:26'),
(21, 6, 'Carbon Footprint Analysis', 'Comprehensive assessments to identify reduction opportunities.', '2025-11-06 03:56:26'),
(22, 6, 'Sustainability Strategy', 'Custom roadmaps for achieving environmental goals.', '2025-11-06 03:56:26'),
(23, 6, 'Green Certifications', 'Guidance through certification processes.', '2025-11-06 03:56:26'),
(24, 6, 'Employee Training', 'Sustainability workshops and education programs.', '2025-11-06 03:56:26'),
(25, 7, 'IoT Energy Management', 'Automation systems that reduce energy consumption by up to 40%.', '2025-11-06 03:56:26'),
(26, 7, 'Smart Office Initiative', 'Optimized lighting and cooling based on occupancy data.', '2025-11-06 03:56:26'),
(27, 7, 'Renewable Integration', 'All office power sourced from solar providers.', '2025-11-06 03:56:26'),
(28, 8, 'Local Sourcing', 'All ingredients purchased from farms within 100 km.', '2025-11-06 03:56:26'),
(29, 8, 'Compostable Packaging', 'All boxes and utensils are fully biodegradable.', '2025-11-06 03:56:26'),
(30, 8, 'Food Waste Donation', 'Unsold food is donated daily to local charities.', '2025-11-06 03:56:26'),
(31, 9, 'Green Hydrogen Production', 'Uses renewable electricity to produce clean hydrogen fuel.', '2025-11-06 03:56:26'),
(32, 9, 'Zero-Emission Transport', 'Hydrogen-powered delivery fleet with no CO₂ output.', '2025-11-06 03:56:26'),
(33, 9, 'Water Recycling', '100% of process water reused in production.', '2025-11-06 03:56:26'),
(34, 10, 'Plastic-Free Packaging', 'Uses biodegradable glass and paper containers.', '2025-11-06 03:56:26'),
(35, 10, 'Refill Program', 'Customers refill skincare bottles to reduce waste.', '2025-11-06 03:56:26'),
(36, 10, 'Ethical Sourcing', 'Ingredients sourced from fair-trade certified suppliers.', '2025-11-06 03:56:26'),
(37, 11, 'Biodegradable Materials', 'All plastics replaced with compostable biopolymers.', '2025-11-06 03:56:26'),
(38, 11, 'Closed-Loop Production', 'Reuses waste material in new manufacturing cycles.', '2025-11-06 03:56:26'),
(39, 11, 'Renewable Power', '100% solar-powered manufacturing operations.', '2025-11-06 03:56:26'),
(40, 12, 'Electric Fleet', 'All delivery vans powered by renewable electricity.', '2025-11-06 03:56:26'),
(41, 12, 'Route Optimization', 'AI routing reduces emissions by 25%.', '2025-11-06 03:56:26'),
(42, 12, 'Carbon-Neutral Deliveries', 'Offsets all carbon emissions from logistics.', '2025-11-06 03:56:26'),
(43, 13, 'Smart Monitoring', 'AI dashboards track and reduce energy waste.', '2025-11-06 03:56:26'),
(44, 13, 'Paperless Operations', '100% digital workflows, eliminating paper use.', '2025-11-06 03:56:26'),
(45, 13, 'Remote Work Policy', 'Encourages remote work to cut transport emissions.', '2025-11-06 03:56:26'),
(46, 14, 'Composting Program', 'All food waste composted for local farms.', '2025-11-06 03:56:26'),
(47, 14, 'Reusable Packaging', 'Customers can return glass jars for refill.', '2025-11-06 03:56:26'),
(48, 14, 'Solar Ovens', 'All baking powered by rooftop solar systems.', '2025-11-06 03:56:26'),
(49, 15, 'Solar Workforce Training', 'Trains local technicians in renewable installations.', '2025-11-06 03:56:26'),
(50, 15, 'End-of-Life Recycling', 'Recycles old solar panels responsibly.', '2025-11-06 03:56:26'),
(51, 15, 'Sustainable Transport', 'Electric vehicles for maintenance operations.', '2025-11-06 03:56:26'),
(52, 16, 'Bulk Refill Stations', 'Zero-packaging refill options for customers.', '2025-11-06 03:56:26'),
(53, 16, 'Plastic Elimination', 'Eliminated all single-use plastics in operations.', '2025-11-06 03:56:26'),
(54, 16, 'Local Partnerships', 'Partners with Singapore eco-brands to reduce imports.', '2025-11-06 03:56:26'),
(55, 17, 'Water Conservation', 'Reuses 90% of wastewater through filtration.', '2025-11-06 03:56:26'),
(56, 17, 'Green Supply Chain', 'Sources raw materials from certified sustainable vendors.', '2025-11-06 03:56:26'),
(57, 17, 'Energy Efficiency', 'All machines upgraded to reduce power use 25%.', '2025-11-06 03:56:26'),
(58, 18, 'Employee Training', 'Conducts quarterly workshops on ESG best practices.', '2025-11-06 03:56:26'),
(59, 18, 'Remote Consulting', 'Uses online sessions to minimize travel emissions.', '2025-11-06 03:56:26'),
(60, 18, 'Client Impact Reporting', 'Provides sustainability metrics for clients annually.', '2025-11-06 03:56:26'),
(61, 19, 'Biofuel Production', 'Converts food waste into renewable biofuels.', '2025-11-06 03:56:26'),
(62, 19, 'Zero Landfill', 'All residues processed into compost.', '2025-11-06 03:56:26'),
(63, 19, 'Community Outreach', 'Educates local businesses on waste-to-energy systems.', '2025-11-06 03:56:26'),
(64, 20, 'Farm-to-Table', 'All produce sourced from nearby hydroponic farms.', '2025-11-06 03:56:26'),
(65, 20, 'Plant-Based Menu', 'Fully vegetarian menu for lower carbon footprint.', '2025-11-06 03:56:26'),
(66, 20, 'Recyclable Utensils', 'All utensils made from cornstarch-based materials.', '2025-11-06 03:56:26'),
(67, 21, 'Smart City Development', 'Implements IoT systems to optimize city infrastructure.', '2025-11-06 03:56:26'),
(68, 21, 'Clean Air Initiative', 'Monitors and reports air quality across districts.', '2025-11-06 03:56:26'),
(69, 21, 'Data Transparency', 'Publishes open data for sustainability research.', '2025-11-06 03:56:26'),
(70, 22, 'Energy-Efficient Products', 'All products meet top-tier efficiency ratings.', '2025-11-06 03:56:26'),
(71, 22, 'Recyclable Materials', 'Uses aluminum and glass for full recyclability.', '2025-11-06 03:56:26'),
(72, 22, 'Zero-Waste Assembly', 'No landfill waste from manufacturing processes.', '2025-11-06 03:56:26'),
(73, 23, 'Waterless Cleaning', 'Eliminates 80% of water use via new technology.', '2025-11-06 03:56:26'),
(74, 23, 'Detergent-Free Wash', 'Uses liquid CO₂ instead of harmful chemicals.', '2025-11-06 03:56:26'),
(75, 23, 'Circular Hanger Return', 'Customers return hangers for reuse.', '2025-11-06 03:56:26'),
(76, 24, 'Hydroponic Farming', 'Uses 90% less water than traditional farming.', '2025-11-06 03:56:26'),
(77, 24, 'Onsite Production', 'Produces food where it’s served to cut transport emissions.', '2025-11-06 03:56:26'),
(78, 24, 'Compost Reuse', 'Uses food scraps as fertilizer for crops.', '2025-11-06 03:56:26'),
(79, 25, 'Hybrid Systems', 'Combines solar and wind for 24/7 energy.', '2025-11-06 03:56:26'),
(80, 25, 'Battery Recycling', 'All batteries recycled at end of life.', '2025-11-06 03:56:26'),
(81, 25, 'Rural Electrification', 'Provides power systems for remote communities.', '2025-11-06 03:56:26'),
(82, 26, 'Eco Product Verification', 'Only sells items verified for sustainability.', '2025-11-06 03:56:26'),
(83, 26, 'Paper-Free Receipts', 'Digital receipts by default for all orders.', '2025-11-06 03:56:26'),
(84, 26, 'Carbon-Neutral Shipping', 'Partners with eco couriers for delivery.', '2025-11-06 03:56:26'),
(85, 27, 'Recycled Steel Use', 'Manufactures equipment from recovered metal.', '2025-11-06 03:56:26'),
(86, 27, 'Power Optimization', 'Upgraded motors reduce electricity demand.', '2025-11-06 03:56:26'),
(87, 27, 'Green Procurement', 'All suppliers meet sustainability standards.', '2025-11-06 03:56:26'),
(88, 28, 'Cloud Efficiency', 'Optimizes server loads to reduce energy waste.', '2025-11-06 03:56:26'),
(89, 28, 'Carbon Tracking', 'Automated CO₂ data for clients via API.', '2025-11-06 03:56:26'),
(90, 28, 'Remote Operations', 'Fully remote team to minimize travel emissions.', '2025-11-06 03:56:26'),
(91, 29, 'Carbon Neutrality', 'Offsets all company operations annually.', '2025-11-06 03:56:26'),
(92, 29, 'Sustainability Advisory', 'Guides clients toward net-zero goals.', '2025-11-06 03:56:26'),
(93, 29, 'Digital First', 'Fully paperless workflow for all client projects.', '2025-11-06 03:56:26'),
(94, 30, 'Battery Innovation', 'Develops high-efficiency solar storage units.', '2025-11-06 03:56:26'),
(95, 30, 'Recycling Initiative', 'All old batteries collected for reuse.', '2025-11-06 03:56:26'),
(96, 30, 'Solar-Powered Offices', 'Runs offices entirely on renewable electricity.', '2025-11-06 03:56:26'),
(97, 31, 'Fair-Trade Sourcing', 'All coffee beans sourced from ethical suppliers.', '2025-11-06 03:56:26'),
(98, 31, 'Reusable Cup Rewards', 'Discounts for customers using reusable cups.', '2025-11-06 03:56:26'),
(99, 31, 'Compost Program', 'Coffee grounds reused for composting.', '2025-11-06 03:56:26'),
(100, 32, 'Compostable Packaging', 'All items biodegrade within 90 days.', '2025-11-06 03:56:26'),
(101, 32, 'Low-Emission Factory', 'Uses renewable power and clean processes.', '2025-11-06 03:56:26'),
(102, 32, 'Waste-Free Production', 'All offcuts recycled into new goods.', '2025-11-06 03:56:26'),
(103, 33, 'Eco Cleaning Supplies', 'Uses biodegradable cleaning products only.', '2025-11-06 03:56:26'),
(104, 33, 'Recycling Service', 'Separates and recycles 95% of waste collected.', '2025-11-06 03:56:26'),
(105, 33, 'Green Fleet', 'Operates hybrid vehicles for service calls.', '2025-11-06 03:56:26'),
(106, 34, 'Smart Grid Analytics', 'AI systems optimize renewable energy flow.', '2025-11-06 03:56:26'),
(107, 34, 'Data Transparency', 'Provides clients with open sustainability reports.', '2025-11-06 03:56:26'),
(108, 34, 'Paperless Office', 'Fully digital operation reduces paper usage to zero.', '2025-11-06 03:56:26'),
(109, 35, 'Eco Awareness Campaigns', 'Hosts monthly workshops on sustainability.', '2025-11-06 03:56:26'),
(110, 35, 'Plastic-Free Retail', 'Completely removes plastic from operations.', '2025-11-06 03:56:26'),
(111, 35, 'Recycling Incentives', 'Discounts for customers who return packaging.', '2025-11-06 03:56:26'),
(112, 36, 'Energy Retrofit', 'Upgrades buildings to reduce power consumption 40%.', '2025-11-06 03:56:26'),
(113, 36, 'Sustainable Materials', 'Only uses recycled or low-impact construction materials.', '2025-11-06 03:56:26'),
(114, 36, 'Green Building Standards', 'All projects meet BCA Green Mark certification.', '2025-11-06 03:56:26');

-- --------------------------------------------------------

--
-- Table structure for table `business_updates`
--

CREATE TABLE `business_updates` (
  `update_id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certifications`
--

CREATE TABLE `certifications` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `certification_name` varchar(255) NOT NULL,
  `certificate_number` varchar(100) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `certifications`
--

INSERT INTO `certifications` (`id`, `business_id`, `certification_name`, `certificate_number`, `issue_date`, `expiry_date`, `verified`, `created_at`) VALUES
(1, 1, 'Green Mark Gold', NULL, NULL, NULL, 0, '2025-11-06 03:56:25'),
(2, 1, 'ISO 14001', NULL, NULL, NULL, 0, '2025-11-06 03:56:25'),
(3, 2, 'Green Mark Certified', NULL, NULL, NULL, 0, '2025-11-06 03:56:25'),
(4, 2, 'Zero Waste', NULL, NULL, NULL, 0, '2025-11-06 03:56:25'),
(5, 3, 'Green Mark Platinum', NULL, NULL, NULL, 0, '2025-11-06 03:56:25'),
(6, 3, 'BCA Green Mark', NULL, NULL, NULL, 0, '2025-11-06 03:56:25'),
(7, 4, 'Green Mark Gold', NULL, NULL, NULL, 0, '2025-11-06 03:56:25'),
(8, 4, 'Plastic-Free', NULL, NULL, NULL, 0, '2025-11-06 03:56:25'),
(9, 5, 'ISO 14001', NULL, NULL, NULL, 0, '2025-11-06 03:56:25'),
(10, 5, 'Carbon Neutral', NULL, NULL, NULL, 0, '2025-11-06 03:56:25'),
(11, 6, 'Green Mark Certified', NULL, NULL, NULL, 0, '2025-11-06 03:56:25'),
(12, 6, 'B Corp', NULL, NULL, NULL, 0, '2025-11-06 03:56:25'),
(13, 7, 'SS ISO 14001 (Environmental Management)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(14, 7, 'Energy Star', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(15, 7, 'Eco Office Certification', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(16, 8, 'Eco Shop / Eco F&B', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(17, 8, 'Fair Trade Certified', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(18, 8, 'Zero Waste SG Certification', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(19, 9, 'SS ISO 50001 (Energy Management)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(20, 9, 'Carbon Neutral / CarbonNeutral®', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(21, 9, 'BCA Green Mark', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(22, 10, 'Singapore Green Label (SGLS)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(23, 10, 'Eco Shop / Eco F&B', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(24, 10, 'B Corp', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(25, 11, 'Cradle to Cradle Certified™', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(26, 11, 'SS ISO 14001 (Environmental Management)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(27, 11, 'Singapore Green Building Product (SGBP)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(28, 12, 'Carbon Neutral / CarbonNeutral®', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(29, 12, 'LowCarbonSG Mark', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(30, 12, 'ISO 26000 (Social Responsibility)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(31, 13, 'SS ISO 50001 (Energy Management)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(32, 13, 'Energy Star', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(33, 13, 'Carbon Disclosure Project (CDP)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(34, 14, 'Zero Waste SG Certification', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(35, 14, 'Eco Shop / Eco F&B', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(36, 14, 'Fair Trade Certified', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(37, 15, 'SS ISO 50001 (Energy Management)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(38, 15, 'BCA Green Mark', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(39, 15, 'Carbon Neutral / CarbonNeutral®', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(40, 16, 'Zero Waste SG Certification', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(41, 16, 'Eco Shop / Eco F&B', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(42, 16, 'Singapore Green Label (SGLS)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(43, 17, 'SS ISO 14001 (Environmental Management)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(44, 17, 'Singapore Green Building Product (SGBP)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(45, 17, 'ISO 26000 (Social Responsibility)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(46, 18, 'B Corp', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(47, 18, 'ISO 26000 (Social Responsibility)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(48, 18, 'Carbon Disclosure Project (CDP)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(49, 19, 'SS ISO 50001 (Energy Management)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(50, 19, 'Carbon Neutral / CarbonNeutral®', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(51, 19, 'LowCarbonSG Mark', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(52, 20, 'Fair Trade Certified', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(53, 20, 'Eco Shop / Eco F&B', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(54, 20, 'B Corp', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(55, 21, 'SS ISO 14001 (Environmental Management)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(56, 21, 'Energy Star', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(57, 21, 'Carbon Disclosure Project (CDP)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(58, 22, 'Energy Star', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(59, 22, 'SS ISO 14001 (Environmental Management)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(60, 22, 'Singapore Green Building Product (SGBP)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(61, 23, 'Zero Waste SG Certification', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(62, 23, 'ISO 26000 (Social Responsibility)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(63, 23, 'LowCarbonSG Mark', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(64, 24, 'Eco Shop / Eco F&B', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(65, 24, 'Fair Trade Certified', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(66, 24, 'B Corp', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(67, 25, 'SS ISO 50001 (Energy Management)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(68, 25, 'BCA Green Mark', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(69, 25, 'Carbon Neutral / CarbonNeutral®', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(70, 26, 'Singapore Green Label (SGLS)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(71, 26, 'Eco Shop / Eco F&B', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(72, 26, 'Fair Trade Certified', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(73, 27, 'SS ISO 14001 (Environmental Management)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(74, 27, 'Energy Star', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(75, 27, 'Singapore Green Building Product (SGBP)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(76, 28, 'SS ISO 14001 (Environmental Management)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(77, 28, 'Eco Office Certification', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(78, 28, 'Carbon Disclosure Project (CDP)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(79, 29, 'B Corp', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(80, 29, 'ISO 26000 (Social Responsibility)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(81, 29, 'Carbon Disclosure Project (CDP)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(82, 30, 'SS ISO 50001 (Energy Management)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(83, 30, 'BCA Green Mark', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(84, 30, 'Carbon Neutral / CarbonNeutral®', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(85, 31, 'Fair Trade Certified', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(86, 31, 'Eco Shop / Eco F&B', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(87, 31, 'Zero Waste SG Certification', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(88, 32, 'Cradle to Cradle Certified™', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(89, 32, 'SS ISO 14001 (Environmental Management)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(90, 32, 'Singapore Green Building Product (SGBP)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(91, 33, 'ISO 26000 (Social Responsibility)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(92, 33, 'LowCarbonSG Mark', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(93, 33, 'Zero Waste SG Certification', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(94, 34, 'SS ISO 50001 (Energy Management)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(95, 34, 'Energy Star', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(96, 34, 'Carbon Disclosure Project (CDP)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(97, 35, 'Singapore Green Label (SGLS)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(98, 35, 'Eco Shop / Eco F&B', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(99, 35, 'B Corp', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(100, 36, 'BCA Green Mark', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(101, 36, 'SS ISO 50001 (Energy Management)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(102, 36, 'ISO 26000 (Social Responsibility)', NULL, NULL, NULL, 0, '2025-11-06 06:21:25'),
(109, 37, 'LEED Certification', '19S82JD', NULL, NULL, 0, '2025-11-06 19:55:09');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `edited` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `user_id`, `content`, `edited`, `created_at`, `updated_at`) VALUES
(1, 1, 6, 'Great tips! We did something similar and reduced waste by 60%.', 0, '2025-11-06 02:56:26', '2025-11-06 03:56:26'),
(2, 1, 7, 'Love this! We need more companies doing this.', 0, '2025-11-06 03:26:26', '2025-11-06 03:56:26'),
(3, 2, 8, 'Love this place! Their coffee is amazing too.', 0, '2025-11-06 00:56:26', '2025-11-06 03:56:26'),
(4, 2, 9, 'Thanks for sharing! Will visit this weekend.', 0, '2025-11-06 01:56:26', '2025-11-06 03:56:26'),
(5, 2, 1, 'Been there! The rooftop garden is beautiful.', 0, '2025-11-06 02:56:26', '2025-11-06 03:56:26'),
(6, 3, 10, 'Check out GreenPack SG - they have great options and reasonable prices!', 0, '2025-11-05 04:56:26', '2025-11-06 03:56:26'),
(7, 3, 2, 'EcoWrap Singapore is also good. They do custom printing too.', 0, '2025-11-05 07:56:26', '2025-11-06 03:56:26'),
(8, 5, 1, 'We did it last year! Initial cost was high but we\'re already seeing 30% reduction in electricity bills.', 0, '2025-11-03 05:56:26', '2025-11-06 03:56:26'),
(9, 6, 3, 'Which bulk stores do you recommend in Singapore?', 0, '2025-11-02 02:56:26', '2025-11-06 03:56:26'),
(10, 6, 4, 'UnPackt and Scoop Wholefoods are my go-to places!', 0, '2025-11-03 03:56:26', '2025-11-06 03:56:26'),
(11, 6, 5, 'Don\'t forget to bring your own bags too!', 0, '2025-11-03 03:56:26', '2025-11-06 03:56:26'),
(12, 6, 7, 'This is so helpful, thank you!', 0, '2025-11-03 03:56:26', '2025-11-06 03:56:26'),
(13, 7, 6, 'What\'s the brand name? Would love to check them out!', 0, '2025-11-01 02:56:26', '2025-11-06 03:56:26'),
(14, 7, 2, 'It\'s called ReThreads SG. They have an online store too!', 0, '2025-11-01 02:56:26', '2025-11-06 03:56:26'),
(15, 8, 1, 'BizSafe and Green Mark are good starting points.', 0, '2025-10-31 02:56:26', '2025-11-06 03:56:26'),
(16, 8, 3, 'I got ISO 14001 certified. Took some work but clients love it!', 0, '2025-10-31 02:56:26', '2025-11-06 03:56:26'),
(17, 8, 5, 'Singapore Green Label Scheme is also worth looking into.', 0, '2025-10-31 02:56:26', '2025-11-06 03:56:26'),
(18, 8, 8, 'Cost can vary a lot depending on certification type.', 0, '2025-11-01 02:56:26', '2025-11-06 03:56:26'),
(19, 8, 9, 'Let me know if you need help with the application process!', 0, '2025-11-01 02:56:26', '2025-11-06 03:56:26');

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `consumer_id` int(11) DEFAULT NULL,
  `business_id` int(11) DEFAULT NULL,
  `business_id_1` int(11) DEFAULT NULL,
  `business_id_2` int(11) DEFAULT NULL,
  `last_message` text DEFAULT NULL,
  `last_message_time` timestamp NULL DEFAULT NULL,
  `consumer_unread_count` int(11) DEFAULT 0,
  `business_unread_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `organizer_id` int(11) NOT NULL,
  `organizer_type` enum('consumer','business') NOT NULL DEFAULT 'consumer',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `start_time`, `end_time`, `location`, `organizer_id`, `organizer_type`, `created_at`, `updated_at`) VALUES
(1, 'Green Business Networking Event', 'Join us for a networking session focused on sustainable business practices. Connect with like-minded entrepreneurs, share ideas, and learn about the latest green initiatives. Free entry with refreshments provided.', '2025-11-12 19:56:26', '2025-11-12 22:56:26', 'Marina Bay Sands Convention Centre, Singapore', 1, 'consumer', '2025-11-06 03:56:26', '2025-11-06 03:56:26'),
(2, 'Sustainable Living Workshop', 'Learn practical tips for reducing your carbon footprint at home. Topics include energy conservation, waste reduction, sustainable shopping habits, and eco-friendly home improvements. Suitable for beginners!', '2025-11-15 19:56:26', '2025-11-15 21:56:26', 'Community Centre, Tampines', 2, 'consumer', '2025-11-06 03:56:26', '2025-11-06 03:56:26'),
(3, 'Solar Energy Open House', 'Visit our showroom and learn about solar energy solutions for your home or business. Free consultations, demonstrations, and Q&A sessions with our solar experts. Special discounts available!', '2025-11-19 19:56:26', '2025-11-20 00:56:26', '50 Jurong Gateway Road, Singapore 608549', 3, 'business', '2025-11-06 03:56:26', '2025-11-06 03:56:26'),
(4, 'Zero-Waste Shopping Tour', 'Join us for a guided tour of zero-waste shopping practices. Learn how to shop package-free, reduce plastic waste, and make sustainable choices. Includes hands-on experience at our store!', '2025-11-10 19:56:26', '2025-11-10 20:56:26', '10 Tampines Central, Singapore 529536', 4, 'business', '2025-11-06 03:56:26', '2025-11-06 03:56:26'),
(5, 'Green Building Materials Exhibition', 'Explore sustainable construction materials and learn about eco-friendly building practices. Meet suppliers, architects, and industry experts. Free admission for all attendees.', '2025-11-26 19:56:26', '2025-11-27 01:56:26', 'Singapore Expo, Hall 5', 5, 'business', '2025-11-06 03:56:26', '2025-11-06 03:56:26'),
(6, 'Carbon Footprint Reduction Seminar', 'Understanding your carbon footprint and how to reduce it. Learn about carbon offsetting, renewable energy options, and sustainable business practices. Includes certification workshop.', '2025-11-17 19:56:26', '2025-11-17 23:56:26', '20 Cecil Street, Singapore 049705', 6, 'business', '2025-11-06 03:56:26', '2025-11-06 03:56:26'),
(7, 'Community Garden Planting Day', 'Help us plant vegetables and herbs in our community garden! Learn about urban farming, composting, and sustainable food practices. All materials provided. Family-friendly event.', '2025-11-08 19:56:26', '2025-11-08 22:56:26', 'Community Garden, Orchard Road', 3, 'consumer', '2025-11-06 03:56:26', '2025-11-06 03:56:26'),
(8, 'E-Waste Recycling Drive', 'Bring your old electronics for proper recycling! We accept phones, laptops, tablets, and other electronic devices. Learn about responsible e-waste disposal and get a free consultation on reducing electronic waste.', '2025-11-13 19:56:26', '2025-11-13 23:56:26', '1 Marina Boulevard, Singapore 018989', 1, 'business', '2025-11-06 03:56:26', '2025-11-06 03:56:26'),
(9, 'Sustainable Fashion Pop-up', 'Discover eco-friendly fashion brands and learn about sustainable clothing choices. Featuring local designers using recycled materials and ethical manufacturing practices.', '2025-11-20 19:56:26', '2025-11-21 00:56:26', '100 Orchard Road, Singapore 238840', 2, 'business', '2025-11-06 03:56:26', '2025-11-06 03:56:26'),
(10, 'Green Tech Innovation Showcase', 'Explore cutting-edge green technology solutions for businesses. Meet innovators, see live demonstrations, and network with sustainability leaders. Ideal for tech enthusiasts and business owners.', '2025-11-23 19:56:26', '2025-11-24 01:56:26', 'Singapore Science Centre', 1, 'business', '2025-11-06 03:56:26', '2025-11-06 03:56:26');

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('consumer','business') NOT NULL DEFAULT 'consumer',
  `business_id` int(11) DEFAULT NULL,
  `registered_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `greenpns`
--

CREATE TABLE `greenpns` (
  `pid` int(11) NOT NULL,
  `productname` varchar(255) NOT NULL,
  `descript` text DEFAULT NULL,
  `pvalue` varchar(50) NOT NULL,
  `bid` int(11) NOT NULL,
  `available` tinyint(1) DEFAULT 1,
  `image_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `greenpns`
--

INSERT INTO `greenpns` (`pid`, `productname`, `descript`, `pvalue`, `bid`, `available`, `image_url`, `created_at`) VALUES
(1, 'Eco Server Hosting', 'Carbon-neutral cloud hosting. Comparison - Green: 50 kWh/month, 0 kg CO₂, 100% renewable energy. Conventional: 180 kWh/month, 95 kg CO₂, 0% renewable. Savings: 130 kWh & 95 kg CO₂ per month.', '99.00++/month', 1, 1, NULL, '2025-11-06 03:56:25'),
(2, 'IT Sustainability Audit', 'Comprehensive assessment', '500.00', 1, 1, NULL, '2025-11-06 03:56:25'),
(3, 'Green IT Consulting', 'Expert advisory services', '150.00/hr', 1, 1, NULL, '2025-11-06 03:56:25'),
(4, 'Organic Breakfast Set', 'Farm-fresh eggs and vegetables. Comparison - Green: 0.5 kg CO₂, 25 L water, 0 g pesticides. Conventional: 2.8 kg CO₂, 85 L water, 45 g pesticides. Savings: 2.3 kg CO₂, 60 L water, 45 g pesticides per meal.', '18.00', 2, 1, NULL, '2025-11-06 03:56:25'),
(5, 'Sustainable Coffee', 'Fair trade organic beans. Comparison - Green: 0.2 kg CO₂, 140 L water, 0 m² deforestation. Conventional: 1.1 kg CO₂, 280 L water, 2.5 m² deforestation. Savings: 0.9 kg CO₂, 140 L water, 2.5 m² forest per kg.', '6.00', 2, 1, NULL, '2025-11-06 03:56:25'),
(6, 'Zero-Waste Lunch Bowl', 'Seasonal local produce', '15.00', 2, 1, NULL, '2025-11-06 03:56:25'),
(7, 'Residential Solar System', 'Complete home installation. Comparison - Green: $30/month energy cost, 0 kg CO₂/year, 10% grid dependency. Conventional: $180/month energy cost, 4,200 kg CO₂/year, 100% grid dependency. Savings: $1,800/year & 4,200 kg CO₂ annually.', '8000.00++', 3, 1, NULL, '2025-11-06 03:56:25'),
(8, 'Commercial Solar Solutions', 'Large-scale installations. Comparison - Green: $500/month energy cost, 0 kg CO₂/year, 5-7 years payback period. Conventional: $3,500/month energy cost, 48,000 kg CO₂/year, N/A payback period. Savings: $36,000/year & 48 tons CO₂ annually.', 'Custom Quote', 3, 1, NULL, '2025-11-06 03:56:25'),
(9, 'Solar Maintenance', 'Annual service package', '$500.00/yr', 3, 1, NULL, '2025-11-06 03:56:25'),
(10, 'Bulk Food Items', 'Organic grains, nuts, and dried goods. Comparison - Green: 0 g plastic packaging, 0.3 kg CO₂/kg, 50 km avg transport distance. Conventional: 85 g plastic packaging, 1.8 kg CO₂/kg, 2,500 km avg transport distance. Savings: 85 g plastic & 1.5 kg CO₂ per kg of food.', 'Varies', 4, 1, NULL, '2025-11-06 03:56:25'),
(11, 'Reusable Products', 'Bottles, bags, and containers. Comparison - Green: 5+ years lifespan, 1,500 items plastic saved, 2 kg CO₂. Conventional: Single use lifespan, 0 items plastic saved, 450 kg CO₂. Savings: 1,500 single-use items & 448 kg CO₂ over 5 years.', '5.00++', 4, 1, NULL, '2025-11-06 03:56:25'),
(12, 'Natural Cleaning Products', 'Eco-friendly cleaning supplies', '8.00++', 4, 1, NULL, '2025-11-06 03:56:25'),
(13, 'Eco Concrete Blocks', 'Recycled aggregate blocks. Comparison - Green: 8 kg CO₂/m³, 65% recycled content, 40 MPa strength. Conventional: 410 kg CO₂/m³, 0% recycled content, 40 MPa strength. Savings: 402 kg CO₂ per cubic meter.', '5.00/block', 5, 1, NULL, '2025-11-06 03:56:25'),
(14, 'Sustainable Insulation', 'Natural fiber insulation. Comparison - Green: 5 kg CO₂/m², 26 MJ/m² embodied energy, biodegradable. Conventional: 45 kg CO₂/m², 250 MJ/m² embodied energy, not biodegradable. Savings: 40 kg CO₂ & 224 MJ per square meter.', '15.00/sqm', 5, 1, NULL, '2025-11-06 03:56:25'),
(15, 'Recycled Steel', 'Construction-grade steel', '$580/ton', 5, 1, NULL, '2025-11-06 03:56:25'),
(16, 'Sustainability Audit', 'Complete environmental assessment. Comparison - Green: 35% avg CO₂ reduction, $15K/year cost savings, 6 months ROI. Conventional: 0% avg CO₂ reduction, $0/year cost savings, N/A ROI. Savings: Typical client saves 150 tons CO₂ & $15K annually.', '2500.00', 6, 1, NULL, '2025-11-06 03:56:25'),
(17, 'Strategy Development', 'Custom sustainability roadmap', '5000.00', 6, 1, NULL, '2025-11-06 03:56:25'),
(18, 'Ongoing Consulting', 'Monthly advisory services', '1000.00/month', 6, 1, NULL, '2025-11-06 03:56:25'),
(19, 'Smart Building Hub', 'IoT automation for lighting, cooling, and energy. Cuts 40% power usage compared to conventional controls.', '1200.00/setup', 7, 1, NULL, '2025-11-06 03:56:25'),
(20, 'Smart Sensor Kit', 'Wireless sensors for temperature, air, and occupancy.', '299.00', 7, 1, NULL, '2025-11-06 03:56:25'),
(21, 'Vegan Margherita', 'Plant-based cheese, organic tomato, sustainable crust.', '16.00', 8, 1, NULL, '2025-11-06 03:56:25'),
(22, 'Compostable Takeaway Set', 'All packaging made from sugarcane fibre.', '2.00', 8, 1, NULL, '2025-11-06 03:56:25'),
(23, 'Hydrogen Fuel Pack', 'Green hydrogen for industrial applications.', 'Custom Quote', 9, 1, NULL, '2025-11-06 03:56:25'),
(24, 'Electrolyzer System', 'Produces hydrogen from solar power.', '25000.00', 9, 1, NULL, '2025-11-06 03:56:25'),
(25, 'Organic Face Cream', 'Zero-plastic jar, vegan ingredients.', '35.00', 10, 1, NULL, '2025-11-06 03:56:25'),
(26, 'Refillable Shampoo', 'Bring-your-own-bottle refill station product.', '18.00', 10, 1, NULL, '2025-11-06 03:56:25'),
(27, 'Bio Plastic Pellet', 'Made from corn starch, decomposes in 90 days.', '2.50/kg', 11, 1, NULL, '2025-11-06 03:56:25'),
(28, 'Compostable Film Roll', 'Used for food packaging.', '80.00/roll', 11, 1, NULL, '2025-11-06 03:56:25'),
(29, 'Electric Van Delivery', 'Zero-emission local delivery under 20 km.', '25.00', 12, 1, NULL, '2025-11-06 03:56:25'),
(30, 'Carbon Offset Plan', 'Optional CO₂ offset per delivery.', '1.00', 12, 1, NULL, '2025-11-06 03:56:25'),
(31, 'Energy Analytics Dashboard', 'AI dashboard showing real-time CO₂ savings.', '499.00/yr', 13, 1, NULL, '2025-11-06 03:56:25'),
(32, 'IoT Power Meter', 'Hardware device for smart monitoring.', '150.00', 13, 1, NULL, '2025-11-06 03:56:25'),
(33, 'Sourdough Loaf', 'Baked using solar ovens.', '8.00', 14, 1, NULL, '2025-11-06 03:56:25'),
(34, 'Compost Subscription', 'Weekly compost pickup for bakery waste.', '10.00/wk', 14, 1, NULL, '2025-11-06 03:56:25'),
(35, 'Home Solar Package', '3 kW residential rooftop setup.', '7000.00', 15, 1, NULL, '2025-11-06 03:56:25'),
(36, 'Solar Maintenance Plan', 'Annual performance check & cleaning.', '450.00/yr', 15, 1, NULL, '2025-11-06 03:56:25'),
(37, 'Refill Station Access', 'Membership to refill eco detergents & food.', '25.00/yr', 16, 1, NULL, '2025-11-06 03:56:25'),
(38, 'Reusable Tote Bag', 'Made from 100% recycled cotton.', '5.00', 16, 1, NULL, '2025-11-06 03:56:25'),
(39, 'Water-Saver Valve', 'Industrial-grade valve that reduces flow 30%.', '80.00', 17, 1, NULL, '2025-11-06 03:56:25'),
(40, 'Recycled Aluminum Frame', 'Used for machines and fixtures.', '120.00', 17, 1, NULL, '2025-11-06 03:56:25'),
(41, 'ESG Report Writing', 'Comprehensive annual sustainability report.', '2500.00', 18, 1, NULL, '2025-11-06 03:56:25'),
(42, 'Carbon Footprint Analysis', 'Company-wide emission audit.', '1800.00', 18, 1, NULL, '2025-11-06 03:56:25'),
(43, 'BioDiesel B20 Blend', 'Renewable diesel made from waste oil.', '2.30/litre', 19, 1, NULL, '2025-11-06 03:56:25'),
(44, 'Food Waste-to-Fuel Kit', 'Mini unit for onsite conversion.', '5000.00', 19, 1, NULL, '2025-11-06 03:56:25'),
(45, 'Plant-Based Burger', '100% vegan, local produce.', '14.00', 20, 1, NULL, '2025-11-06 03:56:25'),
(46, 'Cold-Pressed Juice', 'From hydroponic greens.', '7.00', 20, 1, NULL, '2025-11-06 03:56:25'),
(47, 'Smart Traffic System', 'Sensor-based control reducing congestion 15%.', 'Custom Quote', 21, 1, NULL, '2025-11-06 03:56:25'),
(48, 'Air Quality Monitor', 'IoT node for city data.', '299.00', 21, 1, NULL, '2025-11-06 03:56:25'),
(49, 'LED Tube Light', 'High-efficiency 18 W tube replacing 40 W.', '12.00', 22, 1, NULL, '2025-11-06 03:56:25'),
(50, 'Street Light Fixture', 'Solar-powered LED system.', '650.00', 22, 1, NULL, '2025-11-06 03:56:25'),
(51, 'Waterless Laundry Service', 'Eco cleaning using liquid CO₂ tech.', '8.00/kg', 23, 1, NULL, '2025-11-06 03:56:25'),
(52, 'Pick-Up Subscription', 'Monthly doorstep collection plan.', '40.00/month', 23, 1, NULL, '2025-11-06 03:56:25'),
(53, 'Hydroponic Salad Bowl', 'Lettuce grown onsite.', '12.00', 24, 1, NULL, '2025-11-06 03:56:25'),
(54, 'Smoothie Subscription', 'Weekly fresh blends.', '35.00/week', 24, 1, NULL, '2025-11-06 03:56:25'),
(55, 'Hybrid Microgrid', 'Solar-wind system with battery backup.', 'Custom Quote', 25, 1, NULL, '2025-11-06 03:56:25'),
(56, 'Solar Turbine Combo', 'Compact unit for remote sites.', '32000.00', 25, 1, NULL, '2025-11-06 03:56:25'),
(57, 'Reusable Kitchen Set', 'Eco utensils made from bamboo.', '29.00', 26, 1, NULL, '2025-11-06 03:56:25'),
(58, 'Plastic-Free Starter Box', 'Includes daily-use green items.', '59.00', 26, 1, NULL, '2025-11-06 03:56:25'),
(59, 'Eco Compressor', 'Consumes 40% less power.', '8500.00', 27, 1, NULL, '2025-11-06 03:56:25'),
(60, 'Recycled Steel Casing', 'Machinery casing from recovered metal.', '700.00', 27, 1, NULL, '2025-11-06 03:56:25'),
(61, 'Carbon Tracker API', 'Integrates CO₂ tracking in ERP.', '1500.00', 28, 1, NULL, '2025-11-06 03:56:25'),
(62, 'Sustainability Dashboard', 'ESG data visualization suite.', '990.00/yr', 28, 1, NULL, '2025-11-06 03:56:25'),
(63, 'Net-Zero Certification Prep', 'Audit and compliance package.', '4000.00', 29, 1, NULL, '2025-11-06 03:56:25'),
(64, 'Carbon Offset Portfolio', 'Verified global offset projects.', 'Custom Quote', 29, 1, NULL, '2025-11-06 03:56:25'),
(65, 'Battery Storage Unit', '10 kWh lithium system for homes.', '5000.00', 30, 1, NULL, '2025-11-06 03:56:25'),
(66, 'SolarGrid Monitoring App', 'Tracks solar and battery output.', '99.00/yr', 30, 1, NULL, '2025-11-06 03:56:25'),
(67, 'Fair-Trade Coffee', 'Ethically sourced Arabica beans.', '5.00', 31, 1, NULL, '2025-11-06 03:56:25'),
(68, 'Reusable Cup', 'Discounted drinks when reused.', '12.00', 31, 1, NULL, '2025-11-06 03:56:25'),
(69, 'Compostable Food Box', 'Breaks down in 90 days.', '0.25/box', 32, 1, NULL, '2025-11-06 03:56:25'),
(70, 'Biodegradable Cutlery', 'Made from plant starch.', '0.10/pc', 32, 1, NULL, '2025-11-06 03:56:25'),
(71, 'Green Cleaning Package', 'Uses biodegradable detergents.', '150.00', 33, 1, NULL, '2025-11-06 03:56:25'),
(72, 'Waste Audit Service', 'Measures & reduces facility waste.', '500.00', 33, 1, NULL, '2025-11-06 03:56:25'),
(73, 'Grid Analytics Suite', 'Cloud monitoring for renewables.', '1800.00/yr', 34, 1, NULL, '2025-11-06 03:56:25'),
(74, 'Smart Power Controller', 'Regulates solar inverters.', '350.00', 34, 1, NULL, '2025-11-06 03:56:25'),
(75, 'Eco Starter Kit', 'Includes metal straw, bamboo toothbrush, tote.', '35.00', 35, 1, NULL, '2025-11-06 03:56:25'),
(76, 'Composting Bin', 'Indoor bin with odour filter.', '48.00', 35, 1, NULL, '2025-11-06 03:56:25'),
(77, 'Building Retrofit', 'Upgrade to LED and efficient HVAC.', 'Custom Quote', 36, 1, NULL, '2025-11-06 03:56:25'),
(78, 'Energy Audit', 'Identify cost-saving efficiency upgrades.', '1200.00', 36, 1, NULL, '2025-11-06 03:56:25');

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE `likes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `likes`
--

INSERT INTO `likes` (`id`, `post_id`, `user_id`, `created_at`) VALUES
(1, 1, 2, '2025-11-06 03:56:26'),
(2, 1, 3, '2025-11-06 03:56:26'),
(3, 1, 4, '2025-11-06 03:56:26'),
(4, 1, 5, '2025-11-06 03:56:26'),
(5, 2, 1, '2025-11-06 03:56:26'),
(6, 2, 3, '2025-11-06 03:56:26'),
(7, 2, 4, '2025-11-06 03:56:26'),
(8, 2, 5, '2025-11-06 03:56:26'),
(9, 2, 6, '2025-11-06 03:56:26'),
(10, 3, 1, '2025-11-06 03:56:26'),
(11, 3, 2, '2025-11-06 03:56:26'),
(12, 3, 5, '2025-11-06 03:56:26'),
(13, 3, 7, '2025-11-06 03:56:26'),
(14, 4, 1, '2025-11-06 03:56:26'),
(15, 4, 2, '2025-11-06 03:56:26'),
(16, 4, 3, '2025-11-06 03:56:26'),
(17, 4, 5, '2025-11-06 03:56:26'),
(18, 4, 6, '2025-11-06 03:56:26'),
(19, 4, 7, '2025-11-06 03:56:26'),
(20, 5, 2, '2025-11-06 03:56:26'),
(21, 5, 3, '2025-11-06 03:56:26'),
(22, 5, 4, '2025-11-06 03:56:26'),
(23, 6, 2, '2025-11-06 03:56:26'),
(24, 6, 3, '2025-11-06 03:56:26'),
(25, 6, 4, '2025-11-06 03:56:26'),
(26, 6, 5, '2025-11-06 03:56:26'),
(27, 7, 1, '2025-11-06 03:56:26'),
(28, 7, 3, '2025-11-06 03:56:26'),
(29, 7, 5, '2025-11-06 03:56:26'),
(30, 7, 6, '2025-11-06 03:56:26'),
(31, 8, 1, '2025-11-06 03:56:26'),
(32, 8, 2, '2025-11-06 03:56:26'),
(33, 8, 4, '2025-11-06 03:56:26'),
(34, 8, 6, '2025-11-06 03:56:26');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('consumer','business') NOT NULL,
  `event_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `likes_count` int(11) DEFAULT 0,
  `comments_count` int(11) DEFAULT 0,
  `edited` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `category`, `title`, `content`, `image_url`, `likes_count`, `comments_count`, `edited`, `created_at`, `updated_at`) VALUES
(1, 1, 'Sustainability Tips', 'Top 5 Ways to Reduce Plastic Waste in Your Office', 'Just wanted to share some practical tips we implemented at our company to reduce plastic waste. We switched to reusable water bottles, eliminated single-use cups, and started composting!', NULL, 6, 4, 0, '2025-11-06 01:56:26', '2025-11-06 07:53:46'),
(2, 2, 'Business Spotlight', 'Amazing sustainable cafe in Tiong Bahru!', 'Just discovered this gem - they use zero plastic, source local ingredients, and have a community garden on their rooftop. Highly recommend checking them out!', NULL, 35, 3, 0, '2025-11-05 22:56:26', '2025-11-06 03:56:26'),
(3, 3, 'Q&A', 'Looking for eco-friendly packaging suppliers', 'Hi everyone! I\'m starting a small online business and want to use sustainable packaging. Any recommendations for suppliers in Singapore?', NULL, 18, 2, 0, '2025-11-05 03:56:26', '2025-11-06 03:56:26'),
(4, 4, 'Events', 'Green Business Networking Event Next Week', 'Join us for a networking session focused on sustainable business practices. Free entry, snacks provided. Great opportunity to connect with like-minded entrepreneurs!', NULL, 42, 0, 0, '2025-11-04 03:56:26', '2025-11-06 03:56:26'),
(5, 5, 'General Discussion', 'Solar panels for small businesses - worth it?', 'Thinking about installing solar panels at my shop. Has anyone done this? What was your experience with costs, savings, and installation process?', NULL, 15, 1, 0, '2025-11-03 03:56:26', '2025-11-06 03:56:26'),
(6, 1, 'Sustainability Tips', 'Zero-waste grocery shopping tips', 'Been practicing zero-waste shopping for 6 months now. Here are my top tips: bring your own containers, shop at bulk stores, and meal plan to avoid food waste!', NULL, 28, 4, 0, '2025-11-02 02:56:26', '2025-11-06 03:56:26'),
(7, 2, 'Business Spotlight', 'Local brand making clothes from recycled materials', 'Check out this amazing local fashion brand that creates stylish clothes entirely from recycled plastic bottles and textile waste. Quality is excellent!', NULL, 31, 2, 0, '2025-11-01 02:56:26', '2025-11-06 03:56:26'),
(8, 4, 'Q&A', 'Best green certifications for small businesses?', 'Looking to get my business certified as green/sustainable. What certifications are recognized in Singapore and which ones are worth the investment?', NULL, 22, 5, 0, '2025-10-31 02:56:26', '2025-11-06 03:56:26');

-- --------------------------------------------------------

--
-- Table structure for table `review`
--

CREATE TABLE `review` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `review` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `rating` int(11) DEFAULT NULL,
  `b_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_companies`
--

CREATE TABLE `saved_companies` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `saved_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `user_type` enum('consumer','business') DEFAULT 'consumer',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password_hash`, `name`, `phone`, `location`, `bio`, `user_type`, `created_at`, `last_login`) VALUES
(1, 'sarah.chen@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Chen', NULL, NULL, NULL, 'consumer', '2025-11-06 03:56:26', NULL),
(2, 'michael.wong@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Michael Wong', NULL, NULL, NULL, 'consumer', '2025-11-06 03:56:26', NULL),
(3, 'rachel.ng@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rachel Ng', NULL, NULL, NULL, 'consumer', '2025-11-06 03:56:26', NULL),
(4, 'james.lim@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'James Lim', NULL, NULL, NULL, 'consumer', '2025-11-06 03:56:26', NULL),
(5, 'amanda.koh@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Amanda Koh', NULL, NULL, NULL, 'consumer', '2025-11-06 03:56:26', NULL),
(6, 'john.tan@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Tan', NULL, NULL, NULL, 'consumer', '2025-11-06 03:56:26', NULL),
(7, 'lisa.wong@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lisa Wong', NULL, NULL, NULL, 'consumer', '2025-11-06 03:56:26', NULL),
(8, 'emily.lim@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emily Lim', NULL, NULL, NULL, 'consumer', '2025-11-06 03:56:26', NULL),
(9, 'david.lee@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David Lee', NULL, NULL, NULL, 'consumer', '2025-11-06 03:56:26', NULL),
(10, 'alex.tan@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alex Tan', NULL, NULL, NULL, 'consumer', '2025-11-06 03:56:26', NULL),
(11, 'consumer1@gmail.com', '$2y$10$6ZPw/Yfltt926Dsb9seNpumJcqF9wmnuy8cNL52Z/4QuD8ZrDpyqa', 'consumer1', '', NULL, NULL, 'consumer', '2025-11-06 19:54:33', NULL),
(12, 'business1@gmail.com', '$2y$10$qO4IJmAiwncguXrCYS403uXV20EhMtK3Z4XtzCBu/f0.fB6T2Y0Gu', 'business1', '', NULL, NULL, 'business', '2025-11-06 19:55:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_carbon_offsets`
--

CREATE TABLE `user_carbon_offsets` (
  `offset_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount_kg` decimal(10,2) DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `business_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_interactions`
--

CREATE TABLE `user_interactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `interaction_type` enum('visit','purchase','engagement') NOT NULL,
  `co2_offset` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `businesses`
--
ALTER TABLE `businesses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_verified` (`verified`),
  ADD KEY `idx_sustainability_score` (`sustainability_score`);

--
-- Indexes for table `business_locations`
--
ALTER TABLE `business_locations`
  ADD PRIMARY KEY (`location_id`),
  ADD KEY `business_id` (`business_id`);

--
-- Indexes for table `business_practices`
--
ALTER TABLE `business_practices`
  ADD PRIMARY KEY (`practice_id`),
  ADD KEY `business_id` (`business_id`);

--
-- Indexes for table `business_updates`
--
ALTER TABLE `business_updates`
  ADD PRIMARY KEY (`update_id`),
  ADD KEY `business_id` (`business_id`);

--
-- Indexes for table `certifications`
--
ALTER TABLE `certifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_id` (`business_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_conversation_cb` (`consumer_id`,`business_id`),
  ADD UNIQUE KEY `unique_conversation_bb` (`business_id_1`,`business_id_2`),
  ADD KEY `idx_consumer` (`consumer_id`),
  ADD KEY `idx_business` (`business_id`),
  ADD KEY `idx_updated_at` (`updated_at`),
  ADD KEY `idx_business_id_1` (`business_id_1`),
  ADD KEY `idx_business_id_2` (`business_id_2`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_organizer_id` (`organizer_id`),
  ADD KEY `idx_organizer_type` (`organizer_type`),
  ADD KEY `idx_start_time` (`start_time`),
  ADD KEY `idx_end_time` (`end_time`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_registration` (`event_id`,`user_id`,`user_type`,`business_id`),
  ADD KEY `idx_event_id` (`event_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_user_type` (`user_type`);

--
-- Indexes for table `greenpns`
--
ALTER TABLE `greenpns`
  ADD PRIMARY KEY (`pid`),
  ADD KEY `bid` (`bid`);

--
-- Indexes for table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`post_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `idx_conversation` (`conversation_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_read_at` (`read_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_unread` (`user_id`,`user_type`,`is_read`),
  ADD KEY `idx_event` (`event_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `b_id` (`b_id`);

--
-- Indexes for table `saved_companies`
--
ALTER TABLE `saved_companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_save` (`user_id`,`business_id`),
  ADD KEY `business_id` (`business_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_carbon_offsets`
--
ALTER TABLE `user_carbon_offsets`
  ADD PRIMARY KEY (`offset_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_business_id` (`business_id`);

--
-- Indexes for table `user_interactions`
--
ALTER TABLE `user_interactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_id` (`business_id`),
  ADD KEY `idx_user_offset` (`user_id`,`co2_offset`),
  ADD KEY `idx_interaction_date` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `businesses`
--
ALTER TABLE `businesses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `business_locations`
--
ALTER TABLE `business_locations`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `business_practices`
--
ALTER TABLE `business_practices`
  MODIFY `practice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT for table `business_updates`
--
ALTER TABLE `business_updates`
  MODIFY `update_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `certifications`
--
ALTER TABLE `certifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `greenpns`
--
ALTER TABLE `greenpns`
  MODIFY `pid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `review`
--
ALTER TABLE `review`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `saved_companies`
--
ALTER TABLE `saved_companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `user_carbon_offsets`
--
ALTER TABLE `user_carbon_offsets`
  MODIFY `offset_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_interactions`
--
ALTER TABLE `user_interactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `businesses`
--
ALTER TABLE `businesses`
  ADD CONSTRAINT `businesses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `business_locations`
--
ALTER TABLE `business_locations`
  ADD CONSTRAINT `business_locations_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_practices`
--
ALTER TABLE `business_practices`
  ADD CONSTRAINT `business_practices_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_updates`
--
ALTER TABLE `business_updates`
  ADD CONSTRAINT `business_updates_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `certifications`
--
ALTER TABLE `certifications`
  ADD CONSTRAINT `certifications_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`consumer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_business_id_1` FOREIGN KEY (`business_id_1`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_business_id_2` FOREIGN KEY (`business_id_2`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `greenpns`
--
ALTER TABLE `greenpns`
  ADD CONSTRAINT `greenpns_ibfk_1` FOREIGN KEY (`bid`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `review_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `review_ibfk_2` FOREIGN KEY (`b_id`) REFERENCES `businesses` (`id`);

--
-- Constraints for table `saved_companies`
--
ALTER TABLE `saved_companies`
  ADD CONSTRAINT `saved_companies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_companies_ibfk_2` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_carbon_offsets`
--
ALTER TABLE `user_carbon_offsets`
  ADD CONSTRAINT `user_carbon_offsets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `user_interactions`
--
ALTER TABLE `user_interactions`
  ADD CONSTRAINT `user_interactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_interactions_ibfk_2` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
