import HomeClient from "./home-client";

async function getHomeData() {
  const backendUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || "https://iaccs.org.in";
  try {
    const res = await fetch(`${backendUrl}/get_page.php?slug=home`);
    if (!res.ok) return null;
    return await res.json();
  } catch (e) {
    console.error("Failed to load home page data:", e);
    return null;
  }
}

export default async function Home() {
  const backendUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || "https://iaccs.org.in";
  const pageData = await getHomeData();

  return <HomeClient initialPageData={pageData} backendUrl={backendUrl} />;
}
