import forms from "@tailwindcss/forms";
import colors from "tailwindcss/colors";

/** @type {import('tailwindcss').Config} */
export default {
    content: ["./resources/views/**/*.blade.php", "./resources/js/**/*.js", "./app/Main/**/*.php"],
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                brand: colors.indigo,
            },
            fontFamily: {
                sans: ["Inter", "ui-sans-serif", "system-ui", "sans-serif"],
            },
        },
    },
    plugins: [forms],
};
