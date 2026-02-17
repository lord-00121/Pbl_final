"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.searchVenues = exports.sortVenuesByPrice = exports.filterVenuesByAmenities = exports.filterVenuesByLocation = void 0;
const filterVenuesByLocation = (venues, location) => {
    return venues.filter(venue => venue.location.toLowerCase().includes(location.toLowerCase()));
};
exports.filterVenuesByLocation = filterVenuesByLocation;
const filterVenuesByAmenities = (venues, amenities) => {
    return venues.filter(venue => amenities.every(amenity => venue.amenities.includes(amenity)));
};
exports.filterVenuesByAmenities = filterVenuesByAmenities;
const sortVenuesByPrice = (venues, order = 'asc') => {
    return venues.sort((a, b) => {
        if (order === 'asc') {
            return a.pricing - b.pricing;
        }
        else {
            return b.pricing - a.pricing;
        }
    });
};
exports.sortVenuesByPrice = sortVenuesByPrice;
const searchVenues = (venues, searchTerm) => {
    return venues.filter(venue => venue.name.toLowerCase().includes(searchTerm.toLowerCase()));
};
exports.searchVenues = searchVenues;
