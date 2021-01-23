議事
* 網頁
  * /srv/db1/ly-parser/20201113/full/bills
  * 420MB
  * 約 25926 筆
  * 已處理完，搬進 Bill model 中，檔案存到 s3://twlydata/data/bill/ 內
* 關係文書
  * /srv/db1/ly-parser/20201113/full/docs
  * doc: 16.7G  24867 筆
  * json: 6.2G  18258 筆
議事錄

公報
* 公報章節
  * /srv/db1/ly-parser/20201113/files
  * doc: 21.7GB 4981 筆
  * json: 13.8GB 5973 筆

資料夾架構：
* data/
  * data/bill: 議案單一頁面
  * data/bill-doc: 議案關係文書
  * data/bill-json: 關係文書 word 轉成 html 的 json
  * data/gazette: 公報單一章節

Model:
* Metting 會議
 - meeting\_id (seq)
 - term, session\_period, session_times, meeting_times 屆會次
 - session_type: 1: 全院委員會, 2: 常會, 3: 臨時會, 4: 臨時會(全院委員會)
 - dates 日期，用 json array ，存 YYYY-MM-DD 格式日期
 - data jsonb
   - menu_fetch_at: 是否有抓取目錄，時間
   - 
 - (term, session_period, session_times, meeting_times, session_type) 為 unique
* MeetingMenu 會議目錄
 - meetingmenu_id (seq)
 - meeting_id
 - meeting_no
 - title
 - data
   - agenda_fetch_at: 是否有抓取內容
   - time: 時間
   - location: 地點
   - agenda_type: 議程種類
   - book_id: 不知道幹嘛用的
* MeetingAgenda 會議議程
 - meetingagenda_id (seq)
 - meeting_id
 - meetingmenu_id
 - data
   - title: 案由
   - mapping_bill: 關係文書
   - opinion: 程序委員會意見/議事處意見
   - term_period: 屆會次
   - doc_link
   - pdf_link
* Bill 議案
  - billno 來自 misq 的代碼 char(16)
  - wordno 院第字號（需要從關係文書內取得、審查會沒有、供索引用）
  - datad
    - detail_fetch_at 詳情索取時間
    - detail 解析出來的詳情資訊
    - doc_bak_url 關係文書備份位置(如果多份用 array 處理)
    - doc_parse_at 關係文書解析時間（解完後會在 BillDoc 會有對應資料)
* BillDoc 關係文書解析結果
  - billno
  - data


License:
BSD License
