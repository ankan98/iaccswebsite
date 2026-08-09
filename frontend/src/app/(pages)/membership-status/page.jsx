import MembershipStatusFormClient from "./membership-status-form-client";

async function getPageData() {
  const backendUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || "https://iaccs.org.in";
  try {
    const res = await fetch(`${backendUrl}/get_page.php?slug=membership-status`);
    if (!res.ok) return null;
    return await res.json();
  } catch (e) {
    console.error("Failed to load membership-status page data at build-time:", e);
    return null;
  }
}

export default async function MembershipStatusPage() {
  const pageData = await getPageData();
  return <MembershipStatusFormClient initialPageData={pageData} />;
}
