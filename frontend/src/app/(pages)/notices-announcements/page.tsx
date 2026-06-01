import Link from "next/link";
import DocumentsList from "@/app/components/documents-list";
import HashScroll from "@/app/components/hash-scroll";

export default function NoticesAnnouncements() {
  return (
    <div className="bg-white">
      <HashScroll />
      <section className="w-full bg-white flex flex-col gap-10 py-16 xl:px-[110px] md:px-20 px-5 mt-[40px]">
        <div className="w-full">
          <h1
            className="w-full font-bold text-gray-900 xl:text-[48px] md:text-3xl text-2xl !leading-tight mb-4"
            style={{ fontFamily: "Georgia, serif" }}
          >
            Notices & Announcements
          </h1>
          <p className="text-gray-700 max-w-3xl">
            Stay updated with the latest announcements, notices, and reports
            from ACCS.
          </p>
        </div>

        <div id="announcements">
          <div className="text-center">
            <Link
              href="/notices-announcements#announcements"
              className="bg-green-500 text-white w-[300px] inline-flex items-center gap-3 px-6 py-3 rounded-full mb-6  justify-center h-[52px]"
            >
              <img src="/assets/images/img24.png" alt="" width={28} />
              <h3 className="text-lg md:text-xl font-semibold">
                Announcements
              </h3>
            </Link>
            <DocumentsList
              type="announcements"
              limit={0}
              className="max-w-4xl mx-auto"
              listClassName="space-y-3 text-base md:text-lg"
            />
          </div>
        </div>

        <div id="notices">
          <div className="text-center">
            <Link
              href="/notices-announcements#notices"
              className="bg-green-500 text-white w-[300px] inline-flex items-center gap-3 px-6 py-3 rounded-full mb-6  justify-center h-[52px]"
            >
              <img src="/assets/images/img57.png" alt="" width={28} />
              <h3 className="text-lg md:text-xl font-semibold">Notices</h3>
            </Link>
            <DocumentsList
              type="notices"
              limit={0}
              className="max-w-4xl mx-auto"
              listClassName="space-y-3 text-base md:text-lg"
            />
          </div>
        </div>

        <div id="reports">
          <div className="text-center">
            <Link
              href="/notices-announcements#reports"
              className="bg-green-500 text-white w-[300px] inline-flex items-center gap-3 px-6 py-3 rounded-full mb-6  justify-center h-[52px]"
            >
              <img src="/assets/images/reports.png" alt="" width={28} />
              <h3 className="text-lg md:text-xl font-semibold">
                Reports
              </h3>
            </Link>
            <DocumentsList
              type="reports"
              limit={0}
              className="max-w-4xl mx-auto"
              listClassName="space-y-3 text-base md:text-lg"
            />
          </div>
        </div>
      </section>
    </div>
  );
}
