/**
 * data-mixins.js
 * Contains complex data structures and methods for Directory, Business Profile, and Dashboards.
 */

const coreBusinessData = {
    1: { id: 1, name: "EcoTech Solutions", category: "Technology", address: "1 Marina Boulevard, Singapore 018989", lat: 1.2821, lng: 103.8545, certifications: ["Green Mark Gold", "ISO 14001"], description: "Sustainable IT solutions and green technology provider", sustainabilityScore: 92, icon: "fas fa-laptop-code", },
    2: { id: 2, name: "Green Harvest Cafe", category: "Food and Beverage", address: "100 Orchard Road, Singapore 238840", lat: 1.3048, lng: 103.8318, certifications: ["Green Mark Certified", "Zero Waste"], description: "Farm-to-table organic restaurant with sustainable practices", sustainabilityScore: 88, icon: "fas fa-leaf", },
    3: { id: 3, name: "Solar Power Plus", category: "Energy", address: "50 Jurong Gateway Road, Singapore 608549", lat: 1.3339, lng: 103.7436, certifications: ["Green Mark Platinum", "BCA Green Mark"], description: "Renewable energy installations and solar panel solutions", sustainabilityScore: 95, icon: "fas fa-solar-panel", },
    4: { id: 4, name: "EcoMart Retail", category: "Retail", address: "10 Tampines Central, Singapore 529536", lat: 1.3538, lng: 103.9446, certifications: ["Green Mark Gold", "Plastic-Free"], description: "Zero-waste retail store offering sustainable products", sustainabilityScore: 85, icon: "fas fa-shopping-bag", },
    5: { id: 5, name: "GreenBuild Manufacturing", category: "Manufacturing", address: "15 Woodlands Industrial Park, Singapore 738322", lat: 1.4501, lng: 103.7949, certifications: ["ISO 14001", "Carbon Neutral"], description: "Sustainable manufacturing with eco-friendly materials", sustainabilityScore: 90, icon: "fas fa-industry", },
    6: { id: 6, name: "Eco Consulting Services", category: "Services", address: "20 Cecil Street, Singapore 049705", lat: 1.2825, lng: 103.8499, certifications: ["Green Mark Certified", "B Corp"], description: "Environmental consulting and sustainability advisory services", sustainabilityScore: 87, icon: "fas fa-briefcase", },
};

const directoryMixin = {
    data() {
        return {
            businesses: Object.values(coreBusinessData),
            filteredBusinesses: [],
        };
    },
    methods: {
        loadDemoData() {
            this.filteredBusinesses = this.businesses;
        },
        viewProfile(businessId) {
            window.location.href = 'business_profile.html?id=' + businessId;
        },
    }
};

const businessProfileDataMixin = {
    data() {
        return {
            allBusinessesData: {
                1: { ...coreBusinessData[1], longDescription: "We specialize in helping businesses transition...", stats: { carbonReduced: "125", wasteRecycled: "95%", energySaved: "450 MWh", waterSaved: "2.5M L" }, },
                2: { ...coreBusinessData[2], longDescription: "Our cafe serves 100% organic...", stats: { carbonReduced: "45", wasteRecycled: "100%", energySaved: "120 MWh", waterSaved: "800K L" }, },
                3: { ...coreBusinessData[3], longDescription: "Leading provider of solar energy solutions...", stats: { carbonReduced: "250", wasteRecycled: "98%", energySaved: "2,500 MWh", waterSaved: "500K L" }, },
                4: { ...coreBusinessData[4], longDescription: "Singapore's first zero-waste retail store...", stats: { carbonReduced: "60", wasteRecycled: "92%", energySaved: "180 MWh", waterSaved: "1.2M L" }, },
                5: { ...coreBusinessData[5], longDescription: "We manufacture construction materials...", stats: { carbonReduced: "180", wasteRecycled: "96%", energySaved: "800 MWh", waterSaved: "3.5M L" }, },
                6: { ...coreBusinessData[6], longDescription: "We help businesses achieve their sustainability goals...", stats: { carbonReduced: "300", wasteRecycled: "85%", energySaved: "200 MWh", waterSaved: "1M L" }, },
            }
        };
    },
    methods: {
        loadBusinessFromUrl() {
            const params = new URLSearchParams(window.location.search);
            const businessId = parseInt(params.get('id') || '1');
            
            if (this.allBusinessesData[businessId]) {
                this.business = this.allBusinessesData[businessId];
                this.businessLoaded = true;
            } else {
                alert('Business not found. Redirecting to directory...');
                window.location.href = 'directory.html';
            }
        },
    }
};