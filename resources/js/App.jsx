import React from "react";
import { Outlet } from "react-router-dom";
// import Header from "./components/Header/Header";
// import Footer from "./components/Footer/Footer";

function App() {
    return (
        <div className="app">
            {/*<Header />*/}
            <div>Header</div>
            <main className="main">
                <Outlet />
            </main>
            <div className="footer">
                footer
                {/*<Footer />*/}
            </div>
        </div>
    );
}
export default App;