import csv
import time
from pathlib import Path
from dataclasses import dataclass
from enum import Enum, auto
from xml.etree import ElementTree

import requests
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry


START_ID = 55000
END_ID = 66300

WEB_SERVICE_DEPTH = 1
REQUEST_DELAY = 0.05

OUTPUT_FILE = Path(f"csdb_{START_ID}-{END_ID}.csv")
CHECKPOINT_FILE = Path(f"csdb_{START_ID}-{END_ID}.checkpoint")

BASE_URL = "https://csdb.dk/webservice/"


class ResponseType(Enum):
    SID_FOUND = auto()
    NO_SID = auto()
    ERROR = auto()


@dataclass
class ParsedResponse:
    response_type: ResponseType
    hvsc_path: str = ""
    csdb_id: str = ""
    message: str = ""


def create_session():
    retry_strategy = Retry(
        total=5,
        connect=5,
        read=5,
        status=5,
        backoff_factor=1.5,
        status_forcelist=(429, 500, 502, 503, 504),
        allowed_methods=("GET",),
        raise_on_status=False,
    )

    adapter = HTTPAdapter(
        max_retries=retry_strategy,
        pool_connections=1,
        pool_maxsize=1,
    )

    session = requests.Session()
    session.mount("https://", adapter)

    session.headers.update({
        "User-Agent": "DeepSID-CSDb-importer/1.0",
        "Accept": "application/xml,text/xml;q=0.9,*/*;q=0.1",
        # Uncomment if CSDb dislikes persistent connections.
        # "Connection": "close",
    })

    return session


def read_checkpoint():
    if not CHECKPOINT_FILE.exists():
        return START_ID

    try:
        return int(CHECKPOINT_FILE.read_text().strip()) + 1
    except Exception:
        return START_ID


def save_checkpoint(sid_id):
    CHECKPOINT_FILE.write_text(str(sid_id))


def extract_sid_data(content):
    # CSDb returns plain text "huh" for nonexistent SID IDs.
    text = content.decode("utf-8", errors="replace").strip()

    if text.lower() == "huh":
        return ParsedResponse(
            ResponseType.NO_SID,
            message="No such SID"
        )

    try:
        root = ElementTree.fromstring(content)
    except ElementTree.ParseError as error:
        return ParsedResponse(
            ResponseType.ERROR,
            message=f"Invalid XML: {error}"
        )

    # Sometimes <SID> is the root element.
    sid = root if root.tag == "SID" else root.find("SID")

    if sid is not None:
        hvsc_path = sid.findtext("HVSCPath", "").strip()
        csdb_id = sid.findtext("ID", "").strip()

        if not hvsc_path or not csdb_id:
            return ParsedResponse(
                ResponseType.ERROR,
                message="SID element missing HVSCPath or ID"
            )

        return ParsedResponse(
            ResponseType.SID_FOUND,
            hvsc_path=hvsc_path.lstrip("/"),
            csdb_id=csdb_id
        )

    error = root.find(".//ERROR")
    if error is not None:
        return ParsedResponse(
            ResponseType.ERROR,
            message="".join(error.itertext()).strip()
        )

    children = ", ".join(child.tag for child in root)

    return ParsedResponse(
        ResponseType.ERROR,
        message=f"Unexpected XML (root={root.tag}, children={children})"
    )


def main():

    start_id = read_checkpoint()

    print(f"Starting at SID ID {start_id}")

    new_file = not OUTPUT_FILE.exists()

    with (
        OUTPUT_FILE.open("a", newline="", encoding="utf-8") as csvfile,
        create_session() as session
    ):

        writer = csv.writer(csvfile)

        if new_file:
            writer.writerow(["fullname", "type", "sid_id"])

        for sid_id in range(start_id, END_ID + 1):

            while True:

                try:
                    response = session.get(
                        BASE_URL,
                        params={
                            "type": "sid",
                            "id": sid_id,
                            "depth": WEB_SERVICE_DEPTH,
                        },
                        timeout=(10, 30),
                    )

                except requests.RequestException as error:
                    print(f"{sid_id}: {error}")
                    print("Retrying in 30 seconds...")
                    time.sleep(30)
                    continue

                if response.status_code != 200:
                    print(f"{sid_id}: HTTP {response.status_code}")
                    print("Retrying in 30 seconds...")
                    time.sleep(30)
                    continue

                result = extract_sid_data(response.content)

                if result.response_type == ResponseType.ERROR:

                    print()
                    print("=" * 80)
                    print(f"Unexpected response for SID ID {sid_id}")
                    print(result.message)
                    print(f"Content-Type: {response.headers.get('Content-Type')}")
                    print(f"Length: {len(response.content)} bytes")
                    print("-" * 80)
                    print(response.text[:500])
                    print("=" * 80)
                    print()

                    print("Retrying this SID ID in 30 seconds...")
                    time.sleep(30)
                    continue

                if result.response_type == ResponseType.SID_FOUND:

                    writer.writerow([
                        f"_High Voltage SID Collection/{result.hvsc_path}",
                        "sid",
                        result.csdb_id,
                    ])

                    csvfile.flush()

                    print(f"{sid_id}: {result.hvsc_path}")

                else:

                    print(f"{sid_id}: no SID")

                save_checkpoint(sid_id)
                break

            if REQUEST_DELAY:
                time.sleep(REQUEST_DELAY)

    if CHECKPOINT_FILE.exists():
        CHECKPOINT_FILE.unlink()

    print("Finished.")


if __name__ == "__main__":
    main()