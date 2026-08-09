import type { Metadata } from "next";
import { notFound } from "next/navigation";
import DynamicPageClient from "./dynamic-page-client";

interface PageProps {
  params: Promise<{ slug: string }> | { slug: string };
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const resolvedParams = await params;
  const slug = resolvedParams.slug;

  if (!slug || slug === "dynamic-page") {
    return {
      title: "IACCS Page",
    };
  }

  const pageData = await getPageData(slug);
  if (!pageData || pageData.error) {
    return {
      title: "Page Not Found | IACCS",
    };
  }

  const title = pageData.title || pageData.heading || "IACCS";

  return {
    title: `${title} | IACCS`,
    description: pageData.meta_description || undefined,
    keywords: pageData.meta_keyword || undefined,
  };
}

// Enable static pre-rendering
export async function generateStaticParams() {
  const fallback = [{ slug: "dynamic-page" }];
  try {
    const backendUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || "https://iaccs.org.in";
    const res = await fetch(`${backendUrl}/get_page_slugs.php`);
    if (res.ok) {
      const slugs = await res.json();
      if (Array.isArray(slugs) && slugs.length > 0) {
        const paramSet = new Set<string>();
        slugs.forEach((slug: string) => {
          if (!slug) return;
          paramSet.add(slug);
          paramSet.add(decodeURIComponent(slug));
          paramSet.add(encodeURIComponent(slug));
        });
        return Array.from(paramSet).map((slug) => ({ slug }));
      }
    }
  } catch (e) {
    console.warn("Failed to pre-fetch dynamic page slugs at build-time. Defaulting to fallback parameters.", e);
  }
  return fallback;
}

async function getPageData(rawSlug: string) {
  const backendUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || "https://iaccs.org.in";
  const decodedSlug = decodeURIComponent(rawSlug);
  try {
    const res = await fetch(`${backendUrl}/get_page.php?slug=${encodeURIComponent(decodedSlug)}`);
    if (!res.ok) return null;
    const data = await res.json();
    return data;
  } catch (e) {
    console.error("Failed to load page data:", e);
    return null;
  }
}

export default async function DynamicPage({ params }: PageProps) {
  const backendUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || "https://iaccs.org.in";
  const resolvedParams = await params;
  const slug = resolvedParams.slug;

  if (slug === "dynamic-page") {
    return <DynamicPageClient initialPageData={null} slug="" backendUrl={backendUrl} />;
  }

  const pageData = await getPageData(slug);

  if (!pageData || pageData.error) {
    notFound();
  }

  return <DynamicPageClient initialPageData={pageData} slug={slug} backendUrl={backendUrl} />;
}
