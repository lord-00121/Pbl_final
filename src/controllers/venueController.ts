import { Request, Response } from 'express';
import { VenueService } from '../services/venueService';

export class VenueController {
    private venueService: VenueService;

    constructor() {
        this.venueService = new VenueService();
    }

    async getAllVenues(req: Request, res: Response) {
        try {
            const venues = await this.venueService.getAllVenues();
            res.status(200).json(venues);
        } catch (error) {
            res.status(500).json({ message: 'Error retrieving venues', error });
        }
    }

    async getVenueById(req: Request, res: Response) {
        try {
            const venueId = req.params.id;
            const venue = await this.venueService.getVenueById(venueId);
            if (!venue) {
                return res.status(404).json({ message: 'Venue not found' });
            }
            res.status(200).json(venue);
        } catch (error) {
            res.status(500).json({ message: 'Error retrieving venue', error });
        }
    }

    async createVenue(req: Request, res: Response) {
        try {
            const venueData = req.body;
            const newVenue = await this.venueService.createVenue(venueData);
            res.status(201).json(newVenue);
        } catch (error) {
            res.status(500).json({ message: 'Error creating venue', error });
        }
    }

    async updateVenue(req: Request, res: Response) {
        try {
            const venueId = req.params.id;
            const updatedData = req.body;
            const updatedVenue = await this.venueService.updateVenue(venueId, updatedData);
            res.status(200).json(updatedVenue);
        } catch (error) {
            res.status(500).json({ message: 'Error updating venue', error });
        }
    }

    async uploadImages(req: Request, res: Response) {
        try {
            const venueId = req.params.id;
            const images = (req as any).files; // Assuming files are uploaded using a middleware like multer
            const updatedVenue = await this.venueService.uploadImages(venueId, images);
            res.status(200).json(updatedVenue);
        } catch (error) {
            res.status(500).json({ message: 'Error uploading images', error });
        }
    }

    async manageAmenities(req: Request, res: Response) {
        try {
            const venueId = req.params.id;
            const amenities = req.body.amenities;
            const updatedVenue = await this.venueService.manageAmenities(venueId, amenities);
            res.status(200).json(updatedVenue);
        } catch (error) {
            res.status(500).json({ message: 'Error managing amenities', error });
        }
    }

    async setPricing(req: Request, res: Response) {
        try {
            const venueId = req.params.id;
            const pricing = req.body.pricing;
            const updatedVenue = await this.venueService.setPricing(venueId, pricing);
            res.status(200).json(updatedVenue);
        } catch (error) {
            res.status(500).json({ message: 'Error setting pricing', error });
        }
    }
}