Functions
Check Reservation Date to Prevent Overlapping
CREATE OR REPLACE FUNCTION is_reservation_exist(
    f_kandang_id IN VARCHAR2,
    f_checkin IN TIMESTAMP,
    f_checkout IN TIMESTAMP
) RETURN NUMBER IS
    v_count NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_count
    FROM LAYANANHOTEL
    WHERE KANDANG_ID = f_kandang_id
      AND ONDELETE = 0
      AND (
          CHECKIN <= f_checkout
          AND CHECKOUT >= f_checkin
      );

    RETURN v_count;
END;
/
Procedures
Table Pegawai: CreatePegawai, SelectAllPegawai, SelectPegawaiByUsername, UpdatePegawai, DeletePegawai
-- Procedure to Create a new record in Pegawai
CREATE OR REPLACE PROCEDURE CreatePegawai (
  p_Nama IN VARCHAR2,
  p_Username IN VARCHAR2,
  p_Password IN VARCHAR2,
  p_Posisi IN VARCHAR2,
  p_Email IN VARCHAR2,
  p_NomorTelpon IN VARCHAR2
) AS
BEGIN
  INSERT INTO Pegawai (ID, Nama, Username, Password, Posisi, Email, NomorTelpon)
  VALUES (
    SUBSTR(RAWTOHEX(SYS_GUID()), 1, 8) || '-' ||
    SUBSTR(RAWTOHEX(SYS_GUID()), 9, 4) || '-' ||
    SUBSTR(RAWTOHEX(SYS_GUID()), 13, 4) || '-' ||
    SUBSTR(RAWTOHEX(SYS_GUID()), 17, 4) || '-' ||
    SUBSTR(RAWTOHEX(SYS_GUID()), 21, 12),
    p_Nama, p_Username, p_Password, p_Posisi, p_Email, p_NomorTelpon
  );
END;
/

-- Procedure to Read all records from Pegawai
CREATE OR REPLACE PROCEDURE SelectAllPegawai(p_cursor OUT SYS_REFCURSOR) AS
BEGIN
    OPEN p_cursor FOR 
    SELECT ID, Nama, Username, Posisi, Email, NomorTelpon 
    FROM Pegawai 
    WHERE onDelete = 0;
END;
/

-- Procedure to Read record by Username from Pegawai
CREATE OR REPLACE PROCEDURE SelectPegawaiByUsername(
    p_username IN VARCHAR2,
    p_cursor OUT SYS_REFCURSOR
) AS
BEGIN
    OPEN p_cursor FOR 
    SELECT ID, Nama, Username, Posisi, Email, NomorTelpon 
    FROM Pegawai 
    WHERE Username = p_username AND onDelete = 0;
END;
/

-- Procedure to Update a record in Pegawai
CREATE OR REPLACE PROCEDURE UpdatePegawai (
  p_Nama IN VARCHAR2,
  p_Username IN VARCHAR2,
  p_Password IN VARCHAR2,
  p_Posisi IN VARCHAR2,
  p_Email IN VARCHAR2,
  p_NomorTelpon IN VARCHAR2
) AS
BEGIN
  IF p_Password IS NOT NULL AND p_Password != '' THEN
    UPDATE Pegawai
    SET Nama = p_Nama,
        Password = p_Password,
        Posisi = p_Posisi,
        Email = p_Email,
        NomorTelpon = p_NomorTelpon
    WHERE Username = p_Username AND onDelete = 0;
  ELSE
    UPDATE Pegawai
    SET Nama = p_Nama,
        Posisi = p_Posisi,
        Email = p_Email,
        NomorTelpon = p_NomorTelpon
    WHERE Username = p_Username AND onDelete = 0;
  END IF;
END;
/

-- Procedure to Delete a record (soft delete) in Pegawai
CREATE OR REPLACE PROCEDURE DeletePegawai (
  p_Username IN VARCHAR2
) AS
BEGIN
  UPDATE Pegawai
  SET onDelete = 1
  WHERE Username = p_Username;
END;
/

-- Enable output in SQL*Plus or SQL Developer
SET SERVEROUTPUT ON;

– Test PL/SQL
VARIABLE p_cursor REFCURSOR;
EXEC SelectAllPegawai(:p_cursor);
PRINT p_cursor;
Table LayananHotel: CreateLayananHotel, SelectAllLayananHotel, SelectLayananHotelByID, UpdateLayananHotel, DeleteLayananHotel
– Procedure to create layananhotel (reservation)
CREATE OR REPLACE PROCEDURE CreateLayananHotel(
        p_checkin    IN TIMESTAMP,
        p_checkout   IN TIMESTAMP,
        p_totalbiaya IN NUMBER,
        p_status     IN VARCHAR2,
        p_hewan_id   IN VARCHAR2,
        p_pegawai_id IN VARCHAR2,
        p_kandang_id IN VARCHAR2
    )
    IS
        v_count NUMBER;
        v_kandang_status VARCHAR2(20);
    BEGIN
        -- Check for conflicting reservations
        v_count := is_reservation_exist(p_kandang_id, p_checkin, p_checkout);

        IF v_count > 0 THEN
            RAISE_APPLICATION_ERROR(-20001, 'Reservation conflict: overlapping CHECKIN and CHECKOUT times for the same KANDANG_ID.');
        END IF;

        -- Get the current status of the Kandang
        SELECT STATUS
        INTO v_kandang_status
        FROM Kandang
        WHERE ID = p_kandang_id;

        -- Check if the Kandang status is 'Filled', 'Scheduled', or 'Empty'
        IF v_kandang_status = 'Filled' THEN
            -- Do nothing, as status should not be changed
            NULL;
        ELSIF v_kandang_status = 'Empty' THEN
            -- Change the status to 'Scheduled' if it is empty
            UPDATE Kandang
            SET STATUS = 'Scheduled'
            WHERE ID = p_kandang_id;
        ELSIF v_kandang_status = 'Scheduled' THEN
            -- Do nothing, as status should not be changed
            NULL;
        END IF;

        -- Insert new reservation into LAYANANHOTEL table
        INSERT INTO LAYANANHOTEL (
            CHECKIN, CHECKOUT, TOTALBIAYA, STATUS, HEWAN_ID, PEGAWAI_ID, KANDANG_ID
        ) VALUES (
            p_checkin, p_checkout, p_totalbiaya, p_status, p_hewan_id, p_pegawai_id, p_kandang_id
        );
    END;
/

-- Procedure to Read all records from LayananHotel
CREATE OR REPLACE PROCEDURE SelectAllLayananHotel(p_cursor OUT SYS_REFCURSOR) AS
BEGIN
    OPEN p_cursor FOR 
    SELECT lh.*, h.NAMA AS HEWAN_NAMA, p.NAMA AS PEGAWAI_NAMA, k.Nomor AS KANDANG_NOMOR, k.Ukuran AS KANDANG_UKURAN
       FROM LayananHotel lh 
       JOIN HEWAN h ON lh.HEWAN_ID = h.ID 
       JOIN Pegawai p ON lh.Pegawai_ID = p.ID
       JOIN Kandang k ON lh.Kandang_ID = k.ID
       WHERE lh.onDelete = 0 
       ORDER BY lh.CheckOut;
END;
/

-- Procedure to Read record by ID from LayananHotel
CREATE OR REPLACE PROCEDURE SelectLayananHotelByID(
    p_id IN VARCHAR2,
    p_cursor OUT SYS_REFCURSOR
) AS
BEGIN
    OPEN p_cursor FOR 
    SELECT lh.*, h.nama AS NAMA_HEWAN, k.nomor AS KANDANG_NOMOR, k.ukuran AS KANDANG_UKURAN 
        FROM LayananHotel lh 
        JOIN Hewan h ON lh.Hewan_ID = h.ID 
        JOIN Kandang k ON lh.Kandang_ID = k.ID
        WHERE lh.ID = p_id AND lh.onDelete = 0;
END;
/

-- Procedure to update a reservation
CREATE OR REPLACE PROCEDURE UpdateLayananHotel(
        p_id         IN VARCHAR2,
        p_checkin    IN TIMESTAMP,
        p_checkout   IN TIMESTAMP,
        p_totalbiaya IN NUMBER,
        p_status     IN VARCHAR2,
        p_hewan_id   IN VARCHAR2,
        p_pegawai_id IN VARCHAR2,
        p_kandang_id IN VARCHAR2
    ) IS
        v_count NUMBER;
    BEGIN
        -- Check for conflicting reservations excluding the current one
        SELECT COUNT(*)
        INTO v_count
        FROM LAYANANHOTEL
        WHERE KANDANG_ID = p_kandang_id
          AND ID != p_id
          AND ONDELETE = 0
          AND (
              (p_checkin BETWEEN CHECKIN AND CHECKOUT) OR
              (p_checkout BETWEEN CHECKIN AND CHECKOUT) OR
              (CHECKIN BETWEEN p_checkin AND p_checkout)
          );

        IF v_count > 0 THEN
            RAISE_APPLICATION_ERROR(-20002, 'Reservation conflict: overlapping CHECKIN and CHECKOUT times for the same KANDANG_ID.');
        END IF;

        -- Update reservation
        UPDATE LAYANANHOTEL
        SET
            CHECKIN = p_checkin,
            CHECKOUT = p_checkout,
            TOTALBIAYA = p_totalbiaya,
            STATUS = p_status,
            HEWAN_ID = p_hewan_id,
            PEGAWAI_ID = p_pegawai_id,
            KANDANG_ID = p_kandang_id
        WHERE ID = p_id AND ONDELETE = 0;
    END;
/

-- Procedure to Soft Delete record by ID from LayananHotel
CREATE OR REPLACE PROCEDURE DeleteLayananHotel(p_id IN VARCHAR2) IS
    BEGIN
        UPDATE LAYANANHOTEL
        SET ONDELETE = 1
        WHERE ID = p_id;
    END;
/
Table Kandang: SelectAllKandang
-- Procedure to Read all records from Kandang
CREATE OR REPLACE PROCEDURE SelectAllKandang(p_cursor OUT SYS_REFCURSOR) AS
BEGIN
    OPEN p_cursor FOR 
    SELECT * FROM KANDANG WHERE ONDELETE = 0
      ORDER BY
      CASE UKURAN
          WHEN 'XS' THEN 1
          WHEN 'S' THEN 2
          WHEN 'M' THEN 3
          WHEN 'L' THEN 4
          WHEN 'XL' THEN 5
          WHEN 'XXL' THEN 6
          WHEN 'XXXL' THEN 7
      ELSE 8
      END, 
      NOMOR;
END;
/

Table LayananMedis
-- Procedure to Create a new record in LayananMedis
CREATE OR REPLACE PROCEDURE CreateLayananMedis (
    p_Tanggal        IN TIMESTAMP,
    p_TotalBiaya     IN NUMBER,
    p_Description    IN VARCHAR2,
    p_Status         IN VARCHAR2,
    p_JenisLayanan   IN ArrayJenisLayananMedis,
    p_Pegawai_ID     IN VARCHAR2,
    p_Hewan_ID       IN VARCHAR2
) AS
BEGIN
    INSERT INTO LayananMedis (
        ID, Tanggal, TotalBiaya, Description, Status, JenisLayanan, Pegawai_ID, Hewan_ID
    ) VALUES (
        SUBSTR(RAWTOHEX(SYS_GUID()), 1, 8) || '-' ||
        SUBSTR(RAWTOHEX(SYS_GUID()), 9, 4) || '-' ||
        SUBSTR(RAWTOHEX(SYS_GUID()), 13, 4) || '-' ||
        SUBSTR(RAWTOHEX(SYS_GUID()), 17, 4) || '-' ||
        SUBSTR(RAWTOHEX(SYS_GUID()), 21, 12),
        p_Tanggal,
        p_TotalBiaya,
        p_Description,
        p_Status,
        p_JenisLayanan,
        p_Pegawai_ID,
        p_Hewan_ID
    );
END;
/

	
-- Procedure to Read all records from LayananMedis
CREATE OR REPLACE PROCEDURE SelectAllLayananMedis(p_cursor OUT SYS_REFCURSOR) AS
BEGIN
    OPEN p_cursor FOR 
    SELECT 
        lm.ID, 
        lm.Tanggal, 
        lm.TotalBiaya, 
        lm.Description, 
        lm.Status, 
        lm.JenisLayanan, 
        lm.Pegawai_ID, 
        p.Nama AS NamaPegawai, 
        lm.Hewan_ID, 
        h.Nama AS NamaHewan
    FROM 
        LayananMedis lm
    JOIN 
        Pegawai p ON lm.Pegawai_ID = p.ID
    JOIN 
        Hewan h ON lm.Hewan_ID = h.ID
    WHERE 
        lm.onDelete = 0;
END;
/


-- Procedure to Read a record by ID from LayananMedis
CREATE OR REPLACE PROCEDURE SelectLayananMedisByID(
    p_ID IN VARCHAR2,
    p_cursor OUT SYS_REFCURSOR
) AS
BEGIN
    OPEN p_cursor FOR 
    SELECT 
        lm.ID, 
        lm.Tanggal, 
        lm.TotalBiaya, 
        lm.Description, 
        lm.Status, 
        lm.JenisLayanan, 
        lm.Pegawai_ID, 
        p.Nama AS NamaPegawai, 
        lm.Hewan_ID, 
        h.Nama AS NamaHewan
    FROM 
        LayananMedis lm
    JOIN 
        Pegawai p ON lm.Pegawai_ID = p.ID
    JOIN 
        Hewan h ON lm.Hewan_ID = h.ID
    WHERE 
        lm.ID = p_ID AND lm.onDelete = 0;
END;
/


-- Procedure to Update a record in LayananMedis
CREATE OR REPLACE PROCEDURE UpdateLayananMedis (
    p_ID             IN VARCHAR2,
    p_Tanggal        IN TIMESTAMP,
    p_TotalBiaya     IN NUMBER,
    p_Description    IN VARCHAR2,
    p_Status         IN VARCHAR2,
    p_JenisLayanan   IN ArrayJenisLayananMedis,
    p_Pegawai_ID     IN VARCHAR2,
    p_Hewan_ID       IN VARCHAR2
) AS
BEGIN
    UPDATE LayananMedis
    SET 
        Tanggal = p_Tanggal,
        TotalBiaya = p_TotalBiaya,
        Description = p_Description,
        Status = p_Status,
        JenisLayanan = p_JenisLayanan,
        Pegawai_ID = p_Pegawai_ID,
        Hewan_ID = p_Hewan_ID
    WHERE 
        ID = p_ID AND onDelete = 0;
END;
/


-- Procedure to Delete a record (soft delete) in LayananMedis
CREATE OR REPLACE PROCEDURE DeleteLayananMedis (
    p_ID IN VARCHAR2
) AS
BEGIN
    UPDATE LayananMedis
    SET onDelete = 1
    WHERE ID = p_ID;
END;
/

Table Penjualan
-- Procedure to Create a new record in Transaksi
CREATE OR REPLACE PROCEDURE CreateTransaksi (
  p_TanggalTransaksi   IN TIMESTAMP,
  p_Produk             IN ARRAYPRODUK, -- Adjust the datatype as per your ARRAYPRODUK definition
  p_Pegawai_ID         IN VARCHAR2,
  p_LayananHotel_ID    IN VARCHAR2,
  p_LayananSalon_ID    IN VARCHAR2,
  p_LayananMedis_ID    IN VARCHAR2,
  p_PemilikHewan_ID    IN VARCHAR2
) AS
BEGIN
  INSERT INTO Transaksi (
    ID,
    TANGGALTRANSAKSI,
    PRODUK,
    PEGAWAI_ID,
    LAYANANHOTEL_ID,
    LAYANANSALON_ID,
    LAYANANMEDIS_ID,
    PEMILIKHEWAN_ID
  ) VALUES (
    SUBSTR(RAWTOHEX(SYS_GUID()), 1, 8) || '-' ||
    SUBSTR(RAWTOHEX(SYS_GUID()), 9, 4) || '-' ||
    SUBSTR(RAWTOHEX(SYS_GUID()), 13, 4) || '-' ||
    SUBSTR(RAWTOHEX(SYS_GUID()), 17, 4) || '-' ||
    SUBSTR(RAWTOHEX(SYS_GUID()), 21, 12),
    p_TanggalTransaksi,
    p_Produk,
    p_Pegawai_ID,
    p_LayananHotel_ID,
    p_LayananSalon_ID,
    p_LayananMedis_ID,
    p_PemilikHewan_ID
  );
END;
/

-- Procedure to Read all records from Penjualan
CREATE OR REPLACE PROCEDURE SelectAllPenjualan(p_cursor OUT SYS_REFCURSOR) AS
BEGIN
    OPEN p_cursor FOR 
    SELECT 
        PJ.ID, 
        PJ.TANGGALTRANSAKSI, 
        PJ.PRODUK, 
        PJ.PEGAWAI_ID, 
        PJ.LAYANANHOTEL_ID, 
        PJ.LAYANANSALON_ID, 
        PJ.LAYANANMEDIS_ID, 
        PJ.PEMILIKHEWAN_ID
    FROM Penjualan PJ
    ORDER BY PJ.TANGGALTRANSAKSI DESC;
END;
/

-- Procedure to Read a record by ID from Penjualan
CREATE OR REPLACE PROCEDURE SelectPenjualanByID(
    p_ID IN VARCHAR2,
    p_cursor OUT SYS_REFCURSOR
) AS
BEGIN
    OPEN p_cursor FOR 
    SELECT 
        PJ.ID, 
        PJ.TANGGALTRANSAKSI, 
        PJ.PRODUK, 
        PJ.PEGAWAI_ID, 
        PJ.LAYANANHOTEL_ID, 
        PJ.LAYANANSALON_ID, 
        PJ.LAYANANMEDIS_ID, 
        PJ.PEMILIKHEWAN_ID
    FROM Penjualan PJ
    WHERE PJ.ID = p_ID;
END;
/

-- Procedure to Update a record in Penjualan
CREATE OR REPLACE PROCEDURE UpdatePenjualan (
  p_ID                IN VARCHAR2,
  p_TanggalTransaksi  IN TIMESTAMP,
  p_Produk            IN ARRAYPRODUK, -- Adjust the datatype as per your ARRAYPRODUK definition
  p_Pegawai_ID        IN VARCHAR2,
  p_LayananHotel_ID   IN VARCHAR2,
  p_LayananSalon_ID   IN VARCHAR2,
  p_LayananMedis_ID   IN VARCHAR2,
  p_PemilikHewan_ID   IN VARCHAR2
) AS
BEGIN
  UPDATE Penjualan
  SET 
    TANGGALTRANSAKSI = p_TanggalTransaksi,
    PRODUK = p_Produk,
    PEGAWAI_ID = p_Pegawai_ID,
    LAYANANHOTEL_ID = p_LayananHotel_ID,
    LAYANANSALON_ID = p_LayananSalon_ID,
    LAYANANMEDIS_ID = p_LayananMedis_ID,
    PEMILIKHEWAN_ID = p_PemilikHewan_ID
  WHERE ID = p_ID;
END;
/

-- Procedure to Delete a record from Penjualan
CREATE OR REPLACE PROCEDURE DeletePenjualan (
  p_ID IN VARCHAR2
) AS
BEGIN
  DELETE FROM Penjualan WHERE ID = p_ID;
END;
/

CREATE OR REPLACE PROCEDURE UpdateLayananMedis (
    p_id IN VARCHAR2,
    p_tanggal IN TIMESTAMP,
    p_totalBiaya IN NUMBER,
    p_description IN VARCHAR2,
    p_status IN VARCHAR2,
    p_jenisLayanan IN ArrayJenisLayananMedis,
    p_pegawai_id IN VARCHAR2,
    p_hewan_id IN VARCHAR2
) AS
BEGIN
    -- Update data layanan medis
    UPDATE LayananMedis
    SET Tanggal = p_tanggal,
        TotalBiaya = p_totalBiaya,
        Description = p_description,
        Status = p_status,
        JenisLayanan = p_jenisLayanan,
        Pegawai_ID = p_pegawai_id,
        Hewan_ID = p_hewan_id,
        onDelete = 0
    WHERE ID = p_id;
    
    -- Jika tidak ada baris yang terupdate, throw exception
    IF SQL%ROWCOUNT = 0 THEN
        RAISE_APPLICATION_ERROR(-20001, 'Data layanan medis tidak ditemukan atau sudah dihapus');
    END IF;
END UpdateLayananMedis;
/

-- Tambahkan trigger untuk memastikan total biaya sesuai dengan jenis layanan
CREATE OR REPLACE TRIGGER trg_update_layananmedis_biaya
BEFORE UPDATE ON LayananMedis
FOR EACH ROW
DECLARE
    v_total_biaya NUMBER := 0;
    v_biaya NUMBER;
    v_jenis_id VARCHAR2(36);
BEGIN
    IF :NEW.JenisLayanan IS NOT NULL THEN
        FOR i IN 1..:NEW.JenisLayanan.COUNT LOOP
            v_jenis_id := :NEW.JenisLayanan(i);
            SELECT Biaya INTO v_biaya
            FROM JenisLayananMedis
            WHERE ID = v_jenis_id AND onDelete = 0;
            v_total_biaya := v_total_biaya + v_biaya;
        END LOOP;
        
        -- Update total biaya
        :NEW.TotalBiaya := v_total_biaya;
    END IF;
END;
/ 

-- Procedure to Read all records from Penjualan
CREATE OR REPLACE PROCEDURE SelectAllPenjualan (
    p_search IN VARCHAR2 DEFAULT NULL,
    p_start_date IN DATE DEFAULT NULL,
    p_end_date IN DATE DEFAULT NULL,
    p_offset IN NUMBER DEFAULT 0,
    p_limit IN NUMBER DEFAULT 10,
    p_total_rows OUT NUMBER,
    p_result_cursor OUT SYS_REFCURSOR
)
AS
BEGIN
    -- Count total rows
    SELECT COUNT(*) INTO p_total_rows
    FROM PENJUALAN PJ
    LEFT JOIN Pegawai P ON PJ.PEGAWAI_ID = P.ID
    LEFT JOIN PemilikHewan PH ON PJ.PEMILIKHEWAN_ID = PH.ID
    WHERE PJ.onDelete = 0
    AND PJ.PRODUK IS NOT NULL
    AND PJ.LAYANANMEDIS_ID IS NULL
    AND PJ.LAYANANHOTEL_ID IS NULL  
    AND PJ.LAYANANSALON_ID IS NULL 
    AND (p_search IS NULL 
        OR UPPER(P.NAMA) LIKE '%' || UPPER(p_search) || '%'
        OR UPPER(PH.NAMA) LIKE '%' || UPPER(p_search) || '%')
    AND (p_start_date IS NULL OR TRUNC(PJ.TANGGALTRANSAKSI) >= TRUNC(p_start_date))
    AND (p_end_date IS NULL OR TRUNC(PJ.TANGGALTRANSAKSI) <= TRUNC(p_end_date));

    -- Open cursor for results
    OPEN p_result_cursor FOR
        SELECT 
            PJ.ID,
            PJ.TANGGALTRANSAKSI,
            PJ.TOTALBIAYA as TOTALHARGA,
            P.NAMA as PEGAWAI_NAMA,
            PH.NAMA as PEMILIK_NAMA,
            LISTAGG(PR.NAMA || ' (x' || COUNT(PR.ID) || ')', ', ') 
            WITHIN GROUP (ORDER BY PR.NAMA) as PRODUK_INFO,
            COUNT(PR.ID) as TOTAL_QUANTITY
        FROM PENJUALAN PJ
        LEFT JOIN Pegawai P ON PJ.PEGAWAI_ID = P.ID
        LEFT JOIN PemilikHewan PH ON PJ.PEMILIKHEWAN_ID = PH.ID
        LEFT JOIN TABLE(PJ.PRODUK) TP ON 1=1
        LEFT JOIN Produk PR ON TP.COLUMN_VALUE = PR.ID
        WHERE PJ.onDelete = 0
        AND PJ.PRODUK IS NOT NULL
        AND PJ.LAYANANMEDIS_ID IS NULL
        AND PJ.LAYANANHOTEL_ID IS NULL
        AND PJ.LAYANANSALON_ID IS NULL
        AND (p_search IS NULL 
            OR UPPER(P.NAMA) LIKE '%' || UPPER(p_search) || '%'
            OR UPPER(PH.NAMA) LIKE '%' || UPPER(p_search) || '%')
        AND (p_start_date IS NULL OR TRUNC(PJ.TANGGALTRANSAKSI) >= TRUNC(p_start_date))
        AND (p_end_date IS NULL OR TRUNC(PJ.TANGGALTRANSAKSI) <= TRUNC(p_end_date))
        GROUP BY 
            PJ.ID,
            PJ.TANGGALTRANSAKSI,
            PJ.TOTALBIAYA,
            P.NAMA,
            PH.NAMA
        ORDER BY PJ.TANGGALTRANSAKSI DESC
        OFFSET p_offset ROWS FETCH NEXT p_limit ROWS ONLY;
END;
/

-- Procedure to Read a record by ID from Penjualan
CREATE OR REPLACE PROCEDURE SelectPenjualanByID(
    p_ID IN VARCHAR2,
    p_cursor OUT SYS_REFCURSOR
) AS
BEGIN
    OPEN p_cursor FOR 
    SELECT 
        PJ.ID, 
        PJ.TANGGALTRANSAKSI, 
        PJ.PRODUK,
        PJ.TOTALBIAYA,
        PJ.PEGAWAI_ID, 
        PJ.LAYANANHOTEL_ID, 
        PJ.LAYANANSALON_ID, 
        PJ.LAYANANMEDIS_ID, 
        PJ.PEMILIKHEWAN_ID,
        PJ.onDelete
    FROM Penjualan PJ
    WHERE PJ.ID = p_ID;
END;
/ 

-- Procedure to Create a new record in Transaksi
CREATE OR REPLACE PROCEDURE CreatePenjualan (
  p_TanggalTransaksi   IN TIMESTAMP,
  p_Produk             IN ARRAYPRODUK,
  p_TotalBiaya         IN NUMBER,
  p_Pegawai_ID         IN VARCHAR2,
  p_LayananHotel_ID    IN VARCHAR2,
  p_LayananSalon_ID    IN VARCHAR2,
  p_LayananMedis_ID    IN VARCHAR2,
  p_PemilikHewan_ID    IN VARCHAR2
) AS
BEGIN
  INSERT INTO Penjualan (
    ID,
    TANGGALTRANSAKSI,
    PRODUK,
    TOTALBIAYA,
    PEGAWAI_ID,
    LAYANANHOTEL_ID,
    LAYANANSALON_ID,
    LAYANANMEDIS_ID,
    PEMILIKHEWAN_ID
  ) VALUES (
    SUBSTR(RAWTOHEX(SYS_GUID()), 1, 8) || '-' ||
    SUBSTR(RAWTOHEX(SYS_GUID()), 9, 4) || '-' ||
    SUBSTR(RAWTOHEX(SYS_GUID()), 13, 4) || '-' ||
    SUBSTR(RAWTOHEX(SYS_GUID()), 17, 4) || '-' ||
    SUBSTR(RAWTOHEX(SYS_GUID()), 21, 12),
    p_TanggalTransaksi,
    p_Produk,
    p_TotalBiaya,
    p_Pegawai_ID,
    p_LayananHotel_ID,
    p_LayananSalon_ID,
    p_LayananMedis_ID,
    p_PemilikHewan_ID
  );
  COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE;
END;
/

-- Trigger untuk insert ke tabel penjualan saat status layanan medis berubah menjadi 'Finished'
CREATE OR REPLACE TRIGGER trg_layananmedis_finished
AFTER UPDATE OF Status ON LayananMedis
FOR EACH ROW
WHEN (NEW.Status = 'Finished' AND OLD.Status != 'Finished')
DECLARE
    v_pemilik_id VARCHAR2(36);
BEGIN
    -- Dapatkan ID pemilik hewan
    SELECT H.PEMILIKHEWAN_ID 
    INTO v_pemilik_id
    FROM Hewan H
    WHERE H.ID = :NEW.Hewan_ID;

    -- Insert ke tabel Penjualan
    INSERT INTO Penjualan (
        ID,
        TANGGALTRANSAKSI,
        TOTALBIAYA,
        PEGAWAI_ID,
        LAYANANMEDIS_ID,
        PEMILIKHEWAN_ID
    ) VALUES (
        SUBSTR(RAWTOHEX(SYS_GUID()), 1, 8) || '-' ||
        SUBSTR(RAWTOHEX(SYS_GUID()), 9, 4) || '-' ||
        SUBSTR(RAWTOHEX(SYS_GUID()), 13, 4) || '-' ||
        SUBSTR(RAWTOHEX(SYS_GUID()), 17, 4) || '-' ||
        SUBSTR(RAWTOHEX(SYS_GUID()), 21, 12),
        SYSTIMESTAMP,
        :NEW.TOTALBIAYA,
        :NEW.PEGAWAI_ID,
        :NEW.ID,
        v_pemilik_id
    );
END;
/

-- Trigger untuk insert ke tabel penjualan saat insert layanan medis dengan status 'Finished'
CREATE OR REPLACE TRIGGER trg_layananmedis_finished_insert
AFTER INSERT ON LayananMedis
FOR EACH ROW
WHEN (NEW.Status = 'Finished')
DECLARE
    v_pemilik_id VARCHAR2(36);
BEGIN
    -- Dapatkan ID pemilik hewan
    SELECT H.PEMILIKHEWAN_ID 
    INTO v_pemilik_id
    FROM Hewan H
    WHERE H.ID = :NEW.Hewan_ID;

    -- Insert ke tabel Penjualan
    INSERT INTO Penjualan (
        ID,
        TANGGALTRANSAKSI,
        TOTALBIAYA,
        PEGAWAI_ID,
        LAYANANMEDIS_ID,
        PEMILIKHEWAN_ID
    ) VALUES (
        SUBSTR(RAWTOHEX(SYS_GUID()), 1, 8) || '-' ||
        SUBSTR(RAWTOHEX(SYS_GUID()), 9, 4) || '-' ||
        SUBSTR(RAWTOHEX(SYS_GUID()), 13, 4) || '-' ||
        SUBSTR(RAWTOHEX(SYS_GUID()), 17, 4) || '-' ||
        SUBSTR(RAWTOHEX(SYS_GUID()), 21, 12),
        SYSTIMESTAMP,
        :NEW.TOTALBIAYA,
        :NEW.PEGAWAI_ID,
        :NEW.ID,
        v_pemilik_id
    );
END;
/

COMMIT; 