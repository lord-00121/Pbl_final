import { Router } from 'express';
import { VenueController } from '../controllers/venueController';

const router = Router();
const venueController = new VenueController();

// Public routes to fetch venues
router.get('/', venueController.getAllVenues);
router.get('/:id', venueController.getVenueById);

// Route to create a new venue
router.post('/', venueController.createVenue);

// Route to update venue details
router.put('/:id', venueController.updateVenue);

// Route to upload images
router.post('/:id/images', venueController.uploadImages);

// Route to manage amenities
router.put('/:id/amenities', venueController.manageAmenities);

// Route to set pricing
router.put('/:id/pricing', venueController.setPricing);

export default router;