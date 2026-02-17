"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = __importDefault(require("express"));
const body_parser_1 = __importDefault(require("body-parser"));
const mongoose_1 = __importDefault(require("mongoose"));
const authRoutes_1 = __importDefault(require("./routes/authRoutes"));
const userRoutes_1 = __importDefault(require("./routes/userRoutes"));
const venueRoutes_1 = __importDefault(require("./routes/venueRoutes"));
const bookingRoutes_1 = __importDefault(require("./routes/bookingRoutes"));
const receiptRoutes_1 = __importDefault(require("./routes/receiptRoutes"));
const errorHandler_1 = require("./middleware/errorHandler");
const authMiddleware_1 = require("./middleware/authMiddleware");
const app = (0, express_1.default)();
const PORT = process.env.PORT || 3000;
// Middleware
app.use(body_parser_1.default.json());
app.use(body_parser_1.default.urlencoded({ extended: true }));
app.use(authMiddleware_1.authMiddleware);
// Database connection
mongoose_1.default.connect('mongodb://localhost:27017/sports-venue-renting', {
    useNewUrlParser: true,
    useUnifiedTopology: true,
})
    .then(() => console.log('MongoDB connected'))
    .catch(err => console.error('MongoDB connection error:', err));
// Routes
app.use('/api/auth', authRoutes_1.default);
app.use('/api/users', userRoutes_1.default);
app.use('/api/venues', venueRoutes_1.default);
app.use('/api/bookings', bookingRoutes_1.default);
app.use('/api/receipts', receiptRoutes_1.default);
// Error handling middleware
app.use(errorHandler_1.errorHandler);
// Start the server
app.listen(PORT, () => {
    console.log(`Server is running on http://localhost:${PORT}`);
});
