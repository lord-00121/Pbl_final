"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.AuthController = void 0;
const userService_1 = require("../services/userService");
class AuthController {
    constructor() {
        this.userService = new userService_1.UserService();
    }
    signup(req, res) {
        return __awaiter(this, void 0, void 0, function* () {
            try {
                const { email, password, userType, profileDetails } = req.body;
                const user = yield this.userService.createUser(email, password, userType, profileDetails);
                res.status(201).json({ message: 'User created successfully', user });
            }
            catch (error) {
                res.status(500).json({ message: 'Error creating user', error });
            }
        });
    }
    login(req, res) {
        return __awaiter(this, void 0, void 0, function* () {
            try {
                const { email, password } = req.body;
                const result = yield this.userService.loginUser(email, password);
                res.status(200).json(result);
            }
            catch (error) {
                res.status(401).json({ message: 'Invalid credentials', error });
            }
        });
    }
    adminLogin(req, res) {
        return __awaiter(this, void 0, void 0, function* () {
            try {
                const { email, password } = req.body;
                const result = yield this.userService.loginUser(email, password, true);
                res.status(200).json(result);
            }
            catch (error) {
                res.status(401).json({ message: 'Invalid admin credentials', error });
            }
        });
    }
}
exports.AuthController = AuthController;
