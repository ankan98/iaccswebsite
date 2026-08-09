import type { Metadata } from "next";
import AboutUsClient from "./about-us-client";

async function getPageData() {
  const backendUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || "https://iaccs.org.in";
  try {
    const res = await fetch(`${backendUrl}/get_page.php?slug=about-us`);
    if (!res.ok) return null;
    return await res.json();
  } catch (e) {
    console.error("Failed to load about-us page data:", e);
    return null;
  }
}

export async function generateMetadata(): Promise<Metadata> {
  const pageData = await getPageData();
  const title = pageData?.title || "About Us";
  return {
    title: `${title} | IACCS`,
    description: pageData?.meta_description || undefined,
  };
}

export default async function About() {
  const backendUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || "https://iaccs.org.in";
  const pageData = await getPageData();

  return <AboutUsClient initialPageData={pageData} backendUrl={backendUrl} />;
}
