議事
* 網頁
  * /srv/db1/ly-parser/20201113/full/bills
  * 420MB
  * 約 25926 筆
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
   - word_doc: Word 連結
