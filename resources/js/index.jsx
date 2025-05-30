// import './bootstrap';

import React from 'react';
import ReactDOM from 'react-dom/client';
import {
    createBrowserRouter,
    RouterProvider,
} from 'react-router-dom';
import { routes } from './root/routes';

const router = createBrowserRouter(routes);
const root = ReactDOM.createRoot(document.getElementById("app"));
root.render(
    <React.StrictMode>
        <RouterProvider router={router} />
    </React.StrictMode>
);