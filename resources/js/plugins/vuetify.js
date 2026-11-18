import '@mdi/font/css/materialdesignicons.css';
import 'vuetify/styles';
import { createVuetify } from 'vuetify';

const savedTheme = localStorage.getItem('orisa_theme') || 'orisaDark';

const orisaDark = {
    dark: true,
    colors: {
        background: '#121212',
        surface: '#1E1E1E',
        'surface-variant': '#2A2A2A',
        'on-surface-variant': '#E0E0E0',
        primary: '#FFFFFF',
        'on-primary': '#000000',
        secondary: '#B0B0B0',
        'on-secondary': '#121212',
        error: '#E0E0E0',
        info: '#9E9E9E',
        success: '#D6D6D6',
        warning: '#BDBDBD',
    },
};

const orisaLight = {
    dark: false,
    colors: {
        background: '#F5F5F7',
        surface: '#FFFFFF',
        'surface-variant': '#EAEAEA',
        'on-surface-variant': '#212121',
        primary: '#000000',
        'on-primary': '#FFFFFF',
        secondary: '#424242',
        'on-secondary': '#FFFFFF',
        error: '#121212',
        info: '#616161',
        success: '#212121',
        warning: '#424242',
    },
};

export default createVuetify({
    theme: {
        defaultTheme: savedTheme,
        themes: {
            orisaDark,
            orisaLight,
        },
    },
    defaults: {
        VCard: {
            elevation: 0,
            rounded: 'lg',
        },
        VBtn: {
            rounded: 'md',
            style: 'text-transform: none; font-weight: 600; letter-spacing: 0.3px;',
        },
        VChip: {
            rounded: 'sm',
            style: 'font-weight: 600; letter-spacing: 0.5px;',
        },
    },
});
