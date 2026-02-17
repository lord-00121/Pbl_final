import express from 'express';
import bodyParser from 'body-parser';
import cors from 'cors';
import mongoose from 'mongoose';
import authRoutes from './routes/authRoutes';
import userRoutes from './routes/userRoutes';
import venueRoutes from './routes/venueRoutes';
import bookingRoutes from './routes/bookingRoutes';
import receiptRoutes from './routes/receiptRoutes';
import { errorHandler } from './middleware/errorHandler';
import { authMiddleware } from './middleware/authMiddleware';

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors());
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));
app.use(authMiddleware);
const MONGO_URI = 'mongodb://localhost:27017/sports-venue-renting';
// Database connection
mongoose.connect('mongodb://localhost:27017/sports-venue-renting',)
.then(() => console.log('MongoDB connected'))
.catch(err => console.error('MongoDB connection error:', err));

// Routes
app.use('/auth', authRoutes);
app.use('/users', userRoutes);
app.use('/venues', venueRoutes);
app.use('/bookings', bookingRoutes);
app.use('/receipts', receiptRoutes);

// Error handling middleware
app.use(errorHandler);

// Start the server
app.listen(PORT, () => {
    console.log(`Server is running on http://localhost:${PORT}`);
});