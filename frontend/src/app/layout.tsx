import type { Metadata } from "next";

import { Geist, Geist_Mono } from "next/font/google";

import "./globals.css";

import Header from "./components/header";

import Footer from "./components/footer";



const geistSans = Geist({

  variable: "--font-geist-sans",

  subsets: ["latin"],

});



const geistMono = Geist_Mono({

  variable: "--font-geist-mono",

  subsets: ["latin"],

});



export const metadata: Metadata = {

  title: 'ACCS - The Association for Critical Care Sciences of India',

  description: 'Official website of IACCS (Association for Critical Care Sciences). The leading organization for Critical Care Technology, Emergency Medicine, and Allied Health Professionals in India. Join the network of ICU Technologists, Paramedics, and MT-CCTs.',

  keywords: [

    'Critical Care technology', 

    'Critical care Science', 

    'Association for Critical Care Sciences and technology', 

    'Emergency and Critical Care', 

    'Emergency Medicine', 

    'ACCS', 

    'CCST', 

    'CCT India', 

    'Best Paramedical Course', 

    'Best Allied Health Course in India', 

    'IACCS', 

    'CRITICAL CARE STUDENTS ASSOCIATION', 

    'CRITICAL CARE PROFESSIONAL ASSOCIATION', 

    'ICU TECHNOLOGIST', 

    'MT-CCT', 

    'EMT', 

    'Emergency Medical Technologist', 

    'Paramedic', 

    'ICU Paramedic', 

    'Advance Paramedic Practitioner', 

    'Advance Care Paramedic', 

    'CRITICAL CARE PARAMEDIC', 

    'Advance Critical Care Paramedic', 

    'EMTCCT', 

    'CCU', 

    'Allied health Professionals', 

    'Critical Care Research and Education', 

    'Emergency and Critical Care Organisation'

  ],

  openGraph: {

    type: 'website',

    url: 'https://iaccs.org.in/',

    title: 'IACCS - Association for Critical Care Sciences and Technology',

    description: "Join India's premier association for Critical Care and Emergency Medicine professionals. Resources for ICU Technologists, Paramedics, and Allied Health students.",

    images: [

      {

        url: 'https://iaccs.org.in/iaccslogo.png',

        alt: 'IACCS Logo',

      },

    ],

  },

  twitter: {

    card: 'summary_large_image',

    title: 'IACCS - Association for Critical Care Sciences and Technology',

    description: "Join India's premier association for Critical Care and Emergency Medicine professionals. Resources for ICU Technologists, Paramedics, and Allied Health students.",

    images: ['https://iaccs.org.in/iaccslogo.png'], // Next.js will automatically use this for the twitter:image tag

  },

  icons: {

    apple: "/favicon.ico",

    icon:"/favicon.ico",

  },

};



export default function RootLayout({

  children,

}: {

  children: React.ReactNode;

}) {

  return (

    <html lang="en" className={`${geistSans.variable} ${geistMono.variable}`}>      

      <body>

        <Header />

        <main style={{marginTop:'-80px'}}>{children}</main>

        <Footer />

      </body>

    </html>

  );

}

