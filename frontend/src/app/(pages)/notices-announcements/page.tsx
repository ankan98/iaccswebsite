import NoticesAnnouncementsClient from "./notices-announcements-client";

async function getPageData() {
  const backendUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || "https://iaccs.org.in";
  try {
    const res = await fetch(`${backendUrl}/get_page.php?slug=notices-announcements`);
    if (!res.ok) return null;
    return await res.json();
  } catch (e) {
    console.error("Failed to load notices-announcements page data:", e);
    return null;
  }
}

export default async function NoticesAnnouncements() {
  const backendUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || "https://iaccs.org.in";
  const pageData = await getPageData();

  return <NoticesAnnouncementsClient initialPageData={pageData} backendUrl={backendUrl} />;
}
