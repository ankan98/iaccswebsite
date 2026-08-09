import type { Metadata } from "next";
import ContactUsFormClient from "./contact-us-form-client";

async function getPageData() {
  const backendUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || "https://iaccs.org.in";
  try {
    const res = await fetch(`${backendUrl}/get_page.php?slug=contact-us`);
    if (!res.ok) return null;
    return await res.json();
  } catch (e) {
    console.error("Failed to load contact-us page data at build-time:", e);
    return null;
  }
}

export async function generateMetadata(): Promise<Metadata> {
  const pageData = await getPageData();
  const title = pageData?.title || "Contact Us";
  return {
    title: `${title} | IACCS`,
    description: pageData?.meta_description || undefined,
  };
}

export default async function ContactUsPage() {
  const pageData = await getPageData();
  return <ContactUsFormClient initialPageData={pageData} />;
}
