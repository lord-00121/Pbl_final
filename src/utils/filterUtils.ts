import { IVenue } from '../models/venue';

export const filterVenuesByLocation = (venues: IVenue[], location: string): IVenue[] => {
    return venues.filter(venue => venue.location.toLowerCase().includes(location.toLowerCase()));
};

export const filterVenuesByAmenities = (venues: IVenue[], amenities: string[]): IVenue[] => {
    return venues.filter(venue => 
        amenities.every(amenity => venue.amenities.includes(amenity))
    );
};

export const sortVenuesByPrice = (venues: IVenue[], order: 'asc' | 'desc' = 'asc'): IVenue[] => {
    return venues.sort((a, b) => {
        if (order === 'asc') {
            return a.pricing - b.pricing;
        } else {
            return b.pricing - a.pricing;
        }
    });
};

export const searchVenues = (venues: IVenue[], searchTerm: string): IVenue[] => {
    return venues.filter(venue => 
        venue.name.toLowerCase().includes(searchTerm.toLowerCase())
    );
};