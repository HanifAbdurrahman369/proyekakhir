export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50:  "#FBFFF1",
          100: "#ECFCCA",
          200: "#D8F999",
          300: "#BBF451",
          400: "#9AE600",
          500: "#7CCF00",
          600: "#5EA500",
          700: "#497D00",
          800: "#3C6300",
          900: "#35530E",
        },
      },
      fontFamily: {
        sans: ["Poppins"],
      },
    },
  },
  plugins: [],
}